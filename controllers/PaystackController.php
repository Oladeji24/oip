<?php
// PaystackController.php
// Controller for handling Paystack (NGN payments) API actions

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PaystackConnector;

class PaystackController extends Controller
{
    protected $paystack;

    public function __construct(PaystackConnector $paystack)
    {
        $this->paystack = $paystack;
    }

    // Example: Initialize transaction
    public function initialize(Request $request)
    {
        $email = $request->input('email');
        $amount = $request->input('amount');
        $result = $this->paystack->initializeTransaction($email, $amount);
        return response()->json($result);
    }
}
