<?php

namespace App\Http\Controllers\Admin\Deposit;

use App\Enums\TransactionName;
use App\Http\Controllers\Controller;
use App\Models\Admin\TransferLog;
use App\Models\DepositRequest;
use App\Models\User;
use App\Services\WalletService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepositRequestController extends Controller
{
    public function index()
    {
        $deposits = DepositRequest::with(['user', 'userPayment'])->where('agent_id', Auth::id())->orderBy('id', 'desc')->get();

        return view('admin.deposit_request.index', compact('deposits'));
    }

    public function show($id)
    {
        $deposit = DepositRequest::find($id);

        return view('admin.deposit_request.show', compact('deposit'));
    }

    public function updateStatus(Request $request, DepositRequest $deposit)
    {

        $request->validate([
            'status' => 'required|in:0,1,2|integer',
        ]);

        try {
            $agent = Auth::user();
            $player = User::find($request->player);
            if ($agent->balance < $request->amount) {
                return redirect()->back()->with('error', 'You do not have enough balance to transfer!');
            }

            $deposit->update([
                'status' => $request->status,
            ]);

            
            if ($request->status == 1) {
            $deposit_amount = $request->amount;
            $bonus_amount = $request->bonus;
            $total_amount = $deposit_amount + $bonus_amount;
            app(WalletService::class)->transfer($agent, $player, $total_amount, TransactionName::CreditTransfer);
            }

            // return redirect()->route('admin.agent.deposit')->with('success', 'Deposit request successfully!');
            return redirect()->back()
                ->with('success', 'Deposit Updated successfully')
                ->with('url', env('APP_URL'))
                ->with('player', $request->player)
                ->with('name', $request->name)
                ->with('phone', $request->phone)
                ->with('account_name', $request->account_name)
                ->with('account_no', $request->account_no)
                ->with('payment_method', $request->payment_method)
                ->with('amount', $request->amount)
                ->with('bonus', $request->bonus);
        } catch (Exception $e) {
            return redirect()->route('admin.agent.deposit')->with('error', $e->getMessage());
        }
    }
}
