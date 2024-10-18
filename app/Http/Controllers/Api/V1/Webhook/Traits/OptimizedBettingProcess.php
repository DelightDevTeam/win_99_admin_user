<?php

namespace App\Http\Controllers\Api\V1\Webhook\Traits;

use App\Enums\TransactionName;
use App\Enums\WagerStatus;
use App\Http\Requests\Slot\SlotWebhookRequest;
use App\Models\Admin\GameType;
use App\Models\Admin\GameTypeProduct;
use App\Models\Admin\Product;
use App\Models\SeamlessEvent;
use App\Models\User;
use App\Models\Wager;
use App\Services\WalletService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

trait OptimizedBettingProcess
{
    public function placeBet(SlotWebhookRequest $request)
    {
        $userId = $request->getMember()->id;

        // Try to acquire a Redis lock for the user's wallet
        $lock = Redis::set("wallet:lock:$userId", true, 'EX', 10, 'NX');  // 10-second lock

        if (! $lock) {
            return response()->json(['message' => 'The wallet is currently being updated. Please try again later.'], 409);
        }

        DB::beginTransaction();
        try {
            // Validate the request
            $validator = $request->check();
            if ($validator->fails()) {
                Redis::del("wallet:lock:$userId");

                return $validator->getResponse();
            }

            $before_balance = $request->getMember()->balanceFloat;

            // Create and store the event in the database
            $event = $this->createEvent($request);

            // Retry logic for creating wager transactions with exponential backoff
            $seamless_transactions = $this->retryOnDeadlock(function () use ($validator, $event) {
                return $this->createWagerTransactions($validator->getRequestTransactions(), $event);
            });

            // Process each seamless transaction
            foreach ($seamless_transactions as $seamless_transaction) {
                $this->processTransfer(
                    $request->getMember(),
                    User::adminUser(),
                    TransactionName::Stake,
                    $seamless_transaction->transaction_amount,
                    $seamless_transaction->rate,
                    [
                        'wager_id' => $seamless_transaction->wager_id,
                        'event_id' => $request->getMessageID(),
                        'seamless_transaction_id' => $seamless_transaction->id,
                    ]
                );
            }

            // Refresh balance after transactions
            $request->getMember()->wallet->refreshBalance();
            $after_balance = $request->getMember()->balanceFloat;

            DB::commit();
            Redis::del("wallet:lock::$userId");

            return response()->json([
                'balance_before' => $before_balance,
                'balance_after' => $after_balance,
                'message' => 'Bet placed successfully.',
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Redis::del("wallet:lock::$userId");

            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Creates wagers in chunks and inserts them along with related seamless transactions.
     */
    public function insertBets(array $bets, SeamlessEvent $event)
    {
        $chunkSize = 50; // Define the chunk size
        $batches = array_chunk($bets, $chunkSize);

        $userId = $event->user_id; // Get user_id from SeamlessEvent

        // Process chunks in a transaction to ensure data integrity
        DB::transaction(function () use ($batches, $event) {
            foreach ($batches as $batch) {
                // Call createWagerTransactions for each batch
                $this->createWagerTransactions($batch, $event);
            }
        });

        return count($bets).' bets inserted successfully.';
    }

    /**
     * Creates wagers in chunks and inserts them along with related seamless transactions.
     */
    // current us
    public function createWagerTransactions(array $betBatch, SeamlessEvent $event)
    {
        $retryCount = 0;
        $maxRetries = 5;
        $userId = $event->user_id; // Get user_id from the SeamlessEvent
        $seamlessEventId = $event->id; // Get the ID of the SeamlessEvent

        // Log event and transaction information
        Log::info('Starting createWagerTransactions', ['user_id' => $userId, 'seamless_event_id' => $seamlessEventId, 'betBatch' => $betBatch]);

        // Retry logic for deadlock handling
        do {
            try {
                DB::transaction(function () use ($betBatch, $userId, $seamlessEventId) {
                    // Initialize arrays for batch inserts
                    $wagerData = [];
                    $seamlessTransactionsData = [];

                    // Loop through each bet in the batch
                    foreach ($betBatch as $transaction) {
                        // Log transaction data
                        Log::info('Processing transaction', ['transaction' => $transaction]);

                        // If transaction is an instance of the RequestTransaction object, extract the data
                        if ($transaction instanceof \App\Services\Slot\Dto\RequestTransaction) {
                            // Map the available fields and ensure ActualGameTypeID and ActualProductID are mapped from GameType and ProductID
                            $transactionData = [
                                'Status' => $transaction->Status,
                                'ProductID' => $transaction->ProductID,
                                'GameType' => $transaction->GameType,
                                'TransactionID' => $transaction->TransactionID,
                                'WagerID' => $transaction->WagerID,
                                'BetAmount' => $transaction->BetAmount,
                                'TransactionAmount' => $transaction->TransactionAmount,
                                'PayoutAmount' => $transaction->PayoutAmount,
                                'ValidBetAmount' => $transaction->ValidBetAmount,
                                'Rate' => $transaction->Rate,

                                // Map GameType and ProductID if ActualGameTypeID or ActualProductID are null
                                'ActualGameTypeID' => $transaction->ActualGameTypeID ?? $transaction->GameType,
                                'ActualProductID' => $transaction->ActualProductID ?? $transaction->ProductID,
                            ];

                            // Log the transaction data if needed
                            Log::info('Mapped transaction data', ['transactionData' => $transactionData]);

                        } else {
                            Log::error('Invalid transaction data format.', ['transaction' => $transaction]);
                            throw new \Exception('Invalid transaction data format.');
                        }

                        // Log extracted transaction data
                        Log::info('Extracted transaction data', ['transactionData' => $transactionData]);

                        // Now, use the $transactionData array as expected
                        $existingWager = Wager::where('seamless_wager_id', $transactionData['WagerID'])->lockForUpdate()->first();

                        // Log wager existence check
                        Log::info('Wager existence check', ['existingWager' => $existingWager]);

                        // Fetch game_type and product
                        $game_type = GameType::where('code', $transactionData['GameType'])->first();
                        if (! $game_type) {
                            throw new \Exception("Game type not found for {$transactionData['GameType']}");
                        }

                        $product = Product::where('code', $transactionData['ProductID'])->first();
                        if (! $product) {
                            throw new \Exception("Product not found for {$transactionData['ProductID']}");
                        }

                        // Fetch the rate from GameTypeProduct
                        $game_type_product = GameTypeProduct::where('game_type_id', $game_type->id)
                            ->where('product_id', $product->id)
                            ->first();
                        if (! $game_type_product) {
                            throw new \Exception('GameTypeProduct combination not found.');
                        }

                        $rate = $game_type_product->rate;  // Fetch rate for this transaction
                        Log::info('Fetched rate for transaction', ['rate' => $rate]);

                        // If wager doesn't exist, prepare data for batch insert

                        if (! $existingWager) {
                            $newWager = DB::table('wagers')->insertGetId([
                                'user_id' => $userId,  // Use user_id from the SeamlessEvent
                                'seamless_wager_id' => $transactionData['WagerID'],
                                'status' => $transactionData['TransactionAmount'] > 0 ? WagerStatus::Win : WagerStatus::Lose,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }

                        $seamlessTransactionsData[] = [
                            'user_id' => $userId,
                            'wager_id' => $existingWager ? $existingWager->id : $newWager,  // Ensure wager ID is used
                            'game_type_id' => $transactionData['ActualGameTypeID'],
                            'product_id' => $transactionData['ActualProductID'],
                            'seamless_transaction_id' => $transactionData['TransactionID'],
                            'rate' => $rate,  // Use the fetched rate
                            'transaction_amount' => $transactionData['TransactionAmount'],
                            'bet_amount' => $transactionData['BetAmount'],
                            'valid_amount' => $transactionData['ValidBetAmount'],
                            'status' => $transactionData['Status'],
                            'seamless_event_id' => $seamlessEventId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                    }

                    // Log the prepared data for batch inserts
                    Log::info('Prepared wager data for batch insert', ['wagerData' => $wagerData]);
                    Log::info('Prepared seamless transaction data for batch insert', ['seamlessTransactionsData' => $seamlessTransactionsData]);

                    // Perform batch inserts
                    if (! empty($wagerData)) {
                        DB::table('wagers')->insert($wagerData); // Insert wagers in bulk
                        Log::info('Wagers inserted successfully', ['wagerData' => $wagerData]);
                    }

                    if (! empty($seamlessTransactionsData)) {
                        DB::table('seamless_transactions')->insert($seamlessTransactionsData); // Insert transactions in bulk
                        Log::info('Seamless transactions inserted successfully', ['seamlessTransactionsData' => $seamlessTransactionsData]);
                    }
                });

                break; // Exit the retry loop if successful

            } catch (\Illuminate\Database\QueryException $e) {
                Log::error('Database QueryException occurred', ['exception' => $e, 'retryCount' => $retryCount]);

                if ($e->getCode() === '40001') { // Deadlock error code
                    $retryCount++;
                    if ($retryCount >= $maxRetries) {
                        Log::error('Max retry count reached for deadlock.', ['retryCount' => $retryCount]);
                        throw $e; // Max retries reached, fail
                    }
                    sleep(1); // Wait for a second before retrying
                } else {
                    Log::error('Non-deadlock exception occurred', ['exception' => $e]);
                    throw $e; // Rethrow if it's not a deadlock exception
                }
            }
        } while ($retryCount < $maxRetries);
    }

    //still use
    //     public function createWagerTransactions(array $betBatch, SeamlessEvent $event)
    // {
    //     $retryCount = 0;
    //     $maxRetries = 5;
    //     $userId = $event->user_id; // Get user_id from the SeamlessEvent
    //     $seamlessEventId = $event->id; // Get the ID of the SeamlessEvent

    //     // Retry logic for deadlock handling
    //     do {
    //         try {
    //             DB::transaction(function () use ($betBatch, $userId, $seamlessEventId) {
    //                 // Initialize arrays for batch inserts
    //                 $wagerData = [];
    //                 $seamlessTransactionsData = [];

    //                 // Loop through each bet in the batch
    //                 foreach ($betBatch as $transaction) {
    //                     // If transaction is an instance of the RequestTransaction object, extract the data
    //                     if ($transaction instanceof \App\Services\Slot\Dto\RequestTransaction) {
    //                         $transactionData = [
    //                             'Status' => $transaction->Status,
    //                             'ProductID' => $transaction->ProductID,
    //                             'GameType' => $transaction->GameType,
    //                             'TransactionID' => $transaction->TransactionID,
    //                             'WagerID' => $transaction->WagerID,
    //                             'BetAmount' => $transaction->BetAmount,
    //                             'TransactionAmount' => $transaction->TransactionAmount,
    //                             'PayoutAmount' => $transaction->PayoutAmount,
    //                             'ValidBetAmount' => $transaction->ValidBetAmount,
    //                             'Rate' => $transaction->Rate,
    //                             'ActualGameTypeID' => $transaction->ActualGameTypeID,
    //                             'ActualProductID' => $transaction->ActualProductID,
    //                         ];
    //                     } else {
    //                         throw new \Exception('Invalid transaction data format.');
    //                     }

    //                     // Now, use the $transactionData array as expected
    //                     $existingWager = Wager::where('seamless_wager_id', $transactionData['WagerID'])->lockForUpdate()->first();

    //                     if (!$existingWager) {
    //                         // Collect wager data for batch insert
    //                         $wagerData[] = [
    //                             'user_id' => $userId,  // Use user_id from the SeamlessEvent
    //                             'seamless_wager_id' => $transactionData['WagerID'],
    //                             'status' => $transactionData['TransactionAmount'] > 0 ? WagerStatus::Win : WagerStatus::Lose,
    //                             'created_at' => now(),
    //                             'updated_at' => now(),
    //                         ];
    //                     }

    //                     // Collect seamless transaction data for batch insert
    //                     $seamlessTransactionsData[] = [
    //                         'user_id' => $userId,  // Use user_id from the SeamlessEvent
    //                         'wager_id' => $existingWager ? $existingWager->id : null,
    //                         'game_type_id' => $transactionData['ActualGameTypeID'],
    //                         'product_id' => $transactionData['ActualProductID'],
    //                         'seamless_transaction_id' => $transactionData['TransactionID'],
    //                         'rate' => $transactionData['Rate'],
    //                         'transaction_amount' => $transactionData['TransactionAmount'],
    //                         'bet_amount' => $transactionData['BetAmount'],
    //                         'valid_amount' => $transactionData['ValidBetAmount'],
    //                         'status' => $transactionData['Status'],
    //                         'seamless_event_id' => $seamlessEventId,  // Include seamless_event_id
    //                         'created_at' => now(),
    //                         'updated_at' => now(),
    //                     ];
    //                 }

    //                 // Perform batch inserts
    //                 if (!empty($wagerData)) {
    //                     DB::table('wagers')->insert($wagerData); // Insert wagers in bulk
    //                 }

    //                 if (!empty($seamlessTransactionsData)) {
    //                     DB::table('seamless_transactions')->insert($seamlessTransactionsData); // Insert transactions in bulk
    //                 }
    //             });

    //             break; // Exit the retry loop if successful

    //         } catch (\Illuminate\Database\QueryException $e) {
    //             if ($e->getCode() === '40001') { // Deadlock error code
    //                 $retryCount++;
    //                 if ($retryCount >= $maxRetries) {
    //                     throw $e; // Max retries reached, fail
    //                 }
    //                 sleep(1); // Wait for a second before retrying
    //             } else {
    //                 throw $e; // Rethrow if it's not a deadlock exception
    //             }
    //         }
    //     } while ($retryCount < $maxRetries);
    // }
    /**
     * Create seamless transactions and handle deadlock retries.
     */
    // public function createWagerTransactions($requestTransactions, SeamlessEvent $event, bool $refund = false)
    // {
    //     $seamless_transactions = [];

    //     foreach ($requestTransactions as $requestTransaction) {
    //         DB::transaction(function () use (&$seamless_transactions, $event, $requestTransaction, $refund) {
    //             // Lock for update first to avoid deadlock
    //             $existingWager = Wager::where('seamless_wager_id', $requestTransaction->WagerID)
    //                 ->lockForUpdate()
    //                 ->first();

    //             if (! $existingWager) {
    //                 // Create a new wager if it does not exist
    //                 $wager = Wager::create([
    //                     'user_id' => $event->user_id,
    //                     'seamless_wager_id' => $requestTransaction->WagerID,
    //                 ]);
    //             } else {
    //                 $wager = $existingWager;
    //             }

    //             // Update wager status
    //             if ($refund) {
    //                 $wager->update(['status' => WagerStatus::Refund]);
    //             } elseif (! $wager->wasRecentlyCreated) {
    //                 $wager->update(['status' => $requestTransaction->TransactionAmount > 0 ? WagerStatus::Win : WagerStatus::Lose]);
    //             }

    //             // Retrieve game type and product
    //             $game_type = GameType::where('code', $requestTransaction->GameType)->firstOrFail();
    //             $product = Product::where('code', $requestTransaction->ProductID)->firstOrFail();
    //             $rate = GameTypeProduct::where('game_type_id', $game_type->id)
    //                 ->where('product_id', $product->id)
    //                 ->firstOrFail()->rate;

    //             // Create seamless transaction
    //             $seamless_transactions[] = $event->transactions()->create([
    //                 'user_id' => $event->user_id,
    //                 'wager_id' => $wager->id,
    //                 'game_type_id' => $game_type->id,
    //                 'product_id' => $product->id,
    //                 'seamless_transaction_id' => $requestTransaction->TransactionID,
    //                 'rate' => $rate,
    //                 'transaction_amount' => $requestTransaction->TransactionAmount,
    //                 'bet_amount' => $requestTransaction->BetAmount,
    //                 'valid_amount' => $requestTransaction->ValidBetAmount,
    //                 'status' => $requestTransaction->Status,
    //             ]);
    //         });
    //     }

    //     return $seamless_transactions;
    // }

    /**
     * Process the wallet transfer, handling deadlock retries.
     */
    public function processTransfer(User $from, User $to, TransactionName $transactionName, float $amount, int $rate, array $meta)
    {
        $retryCount = 0;
        $maxRetries = 5;

        do {
            try {
                DB::transaction(function () use ($from, $to, $amount, $transactionName, $meta) {
                    // Fetch the wallet and lock it for update
                    $wallet = $from->wallet()->lockForUpdate()->firstOrFail();

                    // Ensure the version matches for optimistic locking
                    if ($wallet->version !== $from->wallet->version) {
                        throw new \Exception('Version mismatch detected.');
                    }

                    // Update wallet balance
                    $wallet->balance -= $amount;

                    // Increment the version column
                    $wallet->version += 1;

                    // Save the changes to the wallet
                    $wallet->save();

                    // Perform the transfer
                    app(WalletService::class)->transfer($from, $to, abs($amount), $transactionName, $meta);
                });

                break;  // Exit loop if successful
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->getCode() === '40001') {  // Deadlock error code
                    $retryCount++;
                    if ($retryCount >= $maxRetries) {
                        throw $e;  // Max retries reached, fail
                    }
                    sleep(1);  // Wait before retrying
                } else {
                    throw $e;  // Rethrow non-deadlock exceptions
                }
            }
        } while ($retryCount < $maxRetries);
    }

    /**
     * Retry logic for handling deadlocks with exponential backoff.
     */
    private function retryOnDeadlock(callable $callback, $maxRetries = 5)
    {
        $retryCount = 0;

        do {
            try {
                return $callback();
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->getCode() === '40001') {  // Deadlock error code
                    $retryCount++;
                    if ($retryCount >= $maxRetries) {
                        throw $e;  // Max retries reached, fail
                    }
                    sleep(pow(2, $retryCount));  // Exponential backoff
                } else {
                    throw $e;  // Rethrow non-deadlock exceptions
                }
            }
        } while ($retryCount < $maxRetries);
    }

    /**
     * Create the event in the system.
     */
    public function createEvent(SlotWebhookRequest $request): SeamlessEvent
    {
        return SeamlessEvent::create([
            'user_id' => $request->getMember()->id,
            'message_id' => $request->getMessageID(),
            'product_id' => $request->getProductID(),
            'request_time' => $request->getRequestTime(),
            'raw_data' => $request->all(),
        ]);
    }
}
