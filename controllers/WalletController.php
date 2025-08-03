<?php
// WalletController.php
// Controller for managing user wallets: balance, deposit, withdraw, transaction logs

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TransactionLogger;

class WalletController extends Controller
{
    protected $logger;

    public function __construct(TransactionLogger $logger)
    {
        $this->logger = $logger;
    }

    // Get wallet balance
    public function balance(Request $request)
    {
        $userId = $request->user()->id ?? 1;
        // Placeholder: Fetch from DB or Supabase
        $balance = [
            'ngn' => 1000000,
            'usd' => 2000,
        ];
        return response()->json(['success' => true, 'balance' => $balance]);
    }

    // Get transaction logs
    public function logs(Request $request)
    {
        $userId = $request->user()->id ?? 1;
        // Placeholder: Fetch logs from DB or Supabase
        $logs = [
            ['type' => 'deposit', 'amount' => 50000, 'currency' => 'NGN', 'status' => 'completed'],
            ['type' => 'trade', 'amount' => 0.01, 'currency' => 'BTC', 'status' => 'open'],
        ];
        return response()->json(['success' => true, 'logs' => $logs]);
    }

    // Deposit (forwards to payment API)
    public function deposit(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:100',
            'currency' => 'required|string|in:NGN,USD',
        ]);
        $userId = $request->user()->id;
        // Placeholder: Integrate with Paystack/PayPal
        $this->logger->logTransaction($userId, 'deposit', $validated['amount'], $validated['currency'], 'pending');
        return response()->json(['success' => true, 'message' => 'Deposit initiated.']);
    }

    // Withdraw (forwards to withdrawal controller)
    public function withdraw(Request $request)
    {
        $userId = $request->user()->id ?? 1;
        $amount = $request->input('amount');
        $currency = $request->input('currency');
        // Placeholder: Integrate with withdrawal/OTP flow
        $this->logger->logTransaction($userId, 'withdraw', $amount, $currency, 'pending');
        return response()->json(['success' => true, 'message' => 'Withdrawal requested.']);
    }
}
