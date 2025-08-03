<?php
// WithdrawalController.php
// Controller for handling withdrawals with OTP/email verification and admin approval

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SendGridConnector;
use App\Services\TransactionLogger;

class WithdrawalController extends Controller
{
    protected $sendgrid;
    protected $logger;

    public function __construct(SendGridConnector $sendgrid, TransactionLogger $logger)
    {
        $this->sendgrid = $sendgrid;
        $this->logger = $logger;
    }

    // Step 1: Request withdrawal (send OTP/email)
    public function requestWithdrawal(Request $request)
    {
        $userId = $request->user()->id ?? 1;
        $email = $request->input('email');
        $amount = $request->input('amount');
        $currency = $request->input('currency');
        // Generate OTP (for demo, use 123456)
        $otp = '123456';
        // Send OTP via email
        $this->sendgrid->sendEmail($email, 'Withdrawal OTP', "Your OTP is: $otp");
        $this->logger->logAction($userId, 'withdrawal_requested', ['amount' => $amount, 'currency' => $currency]);
        return response()->json(['success' => true, 'message' => 'OTP sent to email.']);
    }

    // Step 2: Confirm withdrawal (user submits OTP)
    public function confirmWithdrawal(Request $request)
    {
        $userId = $request->user()->id ?? 1;
        $otp = $request->input('otp');
        // For demo, accept 123456
        if ($otp !== '123456') {
            return response()->json(['success' => false, 'message' => 'Invalid OTP.'], 400);
        }
        $this->logger->logAction($userId, 'withdrawal_otp_verified');
        // Mark withdrawal as pending admin approval
        return response()->json(['success' => true, 'message' => 'OTP verified. Awaiting admin approval.']);
    }

    // Step 3: Admin approves withdrawal
    public function approveWithdrawal(Request $request)
    {
        $adminId = $request->user()->id ?? 0;
        $userId = $request->input('user_id');
        $amount = $request->input('amount');
        $currency = $request->input('currency');
        // Mark withdrawal as approved (demo only)
        $this->logger->logAction($adminId, 'withdrawal_approved', ['user_id' => $userId, 'amount' => $amount, 'currency' => $currency]);
        return response()->json(['success' => true, 'message' => 'Withdrawal approved.']);
    }
}
