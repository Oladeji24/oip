<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KuCoinController;
use App\Http\Controllers\DerivController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuditLogController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Authentication
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

// Protect sensitive routes
Route::middleware('auth:sanctum')->group(function () {
    // User
    Route::patch('/user/profile', [App\Http\Controllers\UserController::class, 'updateProfile']);
    // KuCoin (Crypto)
    Route::get('/kucoin/balance', [KuCoinController::class, 'balance']);
    Route::post('/kucoin/order', [KuCoinController::class, 'placeOrder']);
    Route::get('/kucoin/ticker', [KuCoinController::class, 'ticker']);
    Route::get('/kucoin/orderbook', [KuCoinController::class, 'orderBook']);
    Route::get('/kucoin/symbols', [KuCoinController::class, 'symbols']);
    Route::get('/kucoin/symbol', [KuCoinController::class, 'symbol']);
    Route::get('/kucoin/trades', [KuCoinController::class, 'trades']);
    Route::post('/kucoin/symbol/clear-cache', [KuCoinController::class, 'clearSymbolCache']);
    // Deriv (Forex)
    Route::get('/deriv/account', [DerivController::class, 'account']);
    Route::post('/deriv/order', [DerivController::class, 'placeOrder']);
    Route::get('/deriv/symbols', [DerivController::class, 'symbols']);
    Route::get('/deriv/symbol', [DerivController::class, 'symbol']);
    Route::get('/deriv/trades', [DerivController::class, 'trades']);
    Route::post('/deriv/symbol/clear-cache', [DerivController::class, 'clearSymbolCache']);
    // Paystack (NGN Payments)
    Route::post('/paystack/initialize', [App\Http\Controllers\PaystackController::class, 'initialize']);
    // PayPal (International Payments)
    Route::post('/paypal/create-payment', [App\Http\Controllers\PayPalController::class, 'createPayment']);
    // SendGrid (Email/OTP)
    Route::post('/sendgrid/send', [App\Http\Controllers\SendGridController::class, 'send']);
    // Bot Trading
    Route::post('/bot/start', [App\Http\Controllers\BotController::class, 'start']);
    Route::post('/bot/stop', [App\Http\Controllers\BotController::class, 'stop']);
    Route::post('/bot/switch', [App\Http\Controllers\BotController::class, 'switch']);
    Route::get('/bot/params', [App\Http\Controllers\BotController::class, 'getParams']);
    Route::post('/bot/update-params', [App\Http\Controllers\BotController::class, 'updateParams']);
    Route::get('/bot/advanced-analytics', [App\Http\Controllers\BotController::class, 'advancedAnalytics']);
    // Transaction Logging
    Route::post('/log/action', [App\Http\Controllers\TransactionController::class, 'logAction']);
    Route::post('/log/transaction', [App\Http\Controllers\TransactionController::class, 'logTransaction']);
    // Withdrawals (OTP/email + admin approval)
    Route::post('/withdrawal/request', [App\Http\Controllers\WithdrawalController::class, 'requestWithdrawal']);
    Route::post('/withdrawal/confirm', [App\Http\Controllers\WithdrawalController::class, 'confirmWithdrawal']);
    Route::post('/withdrawal/approve', [App\Http\Controllers\WithdrawalController::class, 'approveWithdrawal']);
    // Wallet Management
    Route::get('/wallet/balance', [App\Http\Controllers\WalletController::class, 'balance']);
    Route::get('/wallet/logs', [App\Http\Controllers\WalletController::class, 'logs']);
    Route::post('/wallet/deposit', [App\Http\Controllers\WalletController::class, 'deposit']);
    Route::post('/wallet/withdraw', [App\Http\Controllers\WalletController::class, 'withdraw']);
    // User Management (admin)
    Route::get('/users', [App\Http\Controllers\UserController::class, 'index']);
    Route::get('/users/{id}', [App\Http\Controllers\UserController::class, 'show']);
    Route::post('/users/{id}/flag', [App\Http\Controllers\UserController::class, 'flag']);
    Route::middleware('auth:sanctum')->patch('/users/{id}', [App\Http\Controllers\UserController::class, 'update']);
    // Promote/demote user (superadmin only)
    Route::middleware('auth:sanctum')->post('/users/{id}/promote', [App\Http\Controllers\UserController::class, 'promote']);
    Route::middleware('auth:sanctum')->post('/users/{id}/demote', [App\Http\Controllers\UserController::class, 'demote']);
    // Secure user impersonation (superadmin only)
    Route::middleware('auth:sanctum')->post('/users/{id}/impersonate', [App\Http\Controllers\UserController::class, 'impersonate']);
    // Trade Analytics
    Route::get('/analytics', [App\Http\Controllers\TransactionController::class, 'analytics']);
    // Admin/superadmin: Audit logs
    Route::middleware('auth:sanctum')->get('/audit-logs', [AuditLogController::class, 'index']);
    Route::middleware('auth:sanctum')->post('/audit-logs', [AuditLogController::class, 'store']);
});

// Public route for getting user (for auth check)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
