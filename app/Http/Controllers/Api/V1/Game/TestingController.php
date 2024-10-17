<?php

namespace App\Http\Controllers\Api\V1\Game;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\WalletService;
use App\Enums\TransactionName;
use Illuminate\Support\Facades\DB;


class TestingController extends Controller
{
    public function pullReport() {
        return 'here';
    }

    public function AppGetGameList(Request $request)
{
    try {
        // Validate the request input
        $request->validate([
            'balance' => 'required|numeric',
        ]);

        // Fetch the wallet with ID 174
        $wallet = DB::table('wallets')->where('id', 174)->first();

        if (!$wallet) {
            return response()->json(['error' => 'Wallet ID 174 not found.'], 404);
        }

        // Assuming that your wallets table has a holder_id column that links to the users table
        $user = \App\Models\User::find($wallet->holder_id);

        if (!$user) {
            return response()->json(['error' => 'User not found for wallet holder.'], 404);
        }

        // Call WalletService deposit method with the correct user object
        app(WalletService::class)->deposit($user, $request->balance, TransactionName::Rollback);

        return response()->json(['success' => 'Balance updated successfully for wallet ID 174.'], 200);

    } catch (\Exception $e) {
        // Catch any errors and return a server error response
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
    }
}
}