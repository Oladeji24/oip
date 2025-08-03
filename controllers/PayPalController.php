<?php
// PayPalController.php
// Controller for handling PayPal (international payments) API actions

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PayPalConnector;

class PayPalController extends Controller
{
    protected $paypal;

    public function __construct(PayPalConnector $paypal)
    {
        $this->paypal = $paypal;
    }

    // Example: Create payment
    public function createPayment(Request $request)
    {
        $amount = $request->input('amount');
        $currency = $request->input('currency');
        $result = $this->paypal->createPayment($amount, $currency);
        return response()->json($result);
    }
}
