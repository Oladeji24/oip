<?php
// UserNotificationService.php
// Service for sending notifications to users about their trading activity

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Mail\TradingAlert;
use App\Mail\AccountSummary;

class UserNotificationService {
    protected $transactionLogger;

    public function __construct(TransactionLogger $transactionLogger = null) {
        $this->transactionLogger = $transactionLogger ?? app(TransactionLogger::class);
    }

    /**
     * Send a notification to a user about a bot action
     * 
     * @param int $userId User ID
     * @param string $market Market (crypto or forex)
     * @param string $symbol Symbol
     * @param string $action Action (buy, sell, etc.)
     * @param array $data Additional data
     * @return bool Success
     */
    public function sendBotActionNotification($userId, $market, $symbol, $action, $data = []) {
        try {
            // Get user email
            $user = DB::table("users")->where("id", $userId)->first();
            if (!$user || !$user->email) {
                Log::error("Cannot send notification - user not found or no email", [
                    "user_id" => $userId
                ]);
                return false;
            }

            // Log this notification
            $this->transactionLogger->logAction($userId, "notification_sent", [
                "type" => "bot_action",
                "market" => $market,
                "symbol" => $symbol,
                "action" => $action
            ]);

            // Prepare notification data
            $title = "Trading Bot Alert: {$action} {$symbol}";
            $message = "Your trading bot has executed a {$action} order for {$symbol} on the {$market} market.";

            if (isset($data["price"])) {
                $message .= " Price: " . number_format($data["price"], 5);
            }

            if (isset($data["profit"])) {
                $profitStr = number_format($data["profit"], 2);
                $message .= " Profit: " . ($data["profit"] >= 0 ? "+{$profitStr}" : "{$profitStr}");
            }

            // Send the email
            Mail::to($user->email)->queue(new TradingAlert($title, $message, $data));

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send bot action notification", [
                "user_id" => $userId,
                "error" => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send a price alert notification to a user
     * 
     * @param int $userId User ID
     * @param string $symbol Symbol
     * @param string $condition Condition (above, below)
     * @param float $targetPrice Target price
     * @param float $currentPrice Current price
     * @return bool Success
     */
    public function sendPriceAlertNotification($userId, $symbol, $condition, $targetPrice, $currentPrice) {
        try {
            // Get user email
            $user = DB::table("users")->where("id", $userId)->first();
            if (!$user || !$user->email) {
                return false;
            }

            // Log this notification
            $this->transactionLogger->logAction($userId, "notification_sent", [
                "type" => "price_alert",
                "symbol" => $symbol,
                "condition" => $condition,
                "target_price" => $targetPrice,
                "current_price" => $currentPrice
            ]);

            // Prepare notification data
            $title = "Price Alert: {$symbol} is " . ($condition === "above" ? "above" : "below") . " {$targetPrice}";
            $message = "{$symbol} has reached {$currentPrice}, which is " . ($condition === "above" ? "above" : "below") . " your target price of {$targetPrice}.";

            $data = [
                "symbol" => $symbol,
                "condition" => $condition,
                "target_price" => $targetPrice,
                "current_price" => $currentPrice,
                "time" => now()
            ];

            // Send the email
            Mail::to($user->email)->queue(new TradingAlert($title, $message, $data));

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send price alert notification", [
                "user_id" => $userId,
                "error" => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send a weekly trading summary to a user
     * 
     * @param int $userId User ID
     * @param string $period Period (daily, weekly, monthly)
     * @return bool Success
     */
    public function sendTradingSummary($userId, $period = "weekly") {
        try {
            // Get user email
            $user = DB::table("users")->where("id", $userId)->first();
            if (!$user || !$user->email) {
                return false;
            }

            // Determine date range
            $endDate = now();
            $startDate = null;

            switch ($period) {
                case "daily":
                    $startDate = $endDate->copy()->subDay();
                    break;
                case "weekly":
                    $startDate = $endDate->copy()->subWeek();
                    break;
                case "monthly":
                    $startDate = $endDate->copy()->subMonth();
                    break;
                default:
                    $startDate = $endDate->copy()->subWeek();
                    break;
            }

            // Get trading summary
            $trades = DB::table("bot_trades")
                ->where("user_id", $userId)
                ->where(function($query) use ($startDate, $endDate) {
                    $query->whereBetween("opened_at", [$startDate, $endDate])
                          ->orWhereBetween("closed_at", [$startDate, $endDate]);
                })
                ->get();

            $openTrades = $trades->whereNull("exit")->count();
            $closedTrades = $trades->whereNotNull("exit")->count();
            $winningTrades = $trades->where("profit", ">", 0)->count();
            $losingTrades = $trades->where("profit", "<", 0)->count();
            $totalProfit = $trades->sum("profit");

            // Get activity summary
            $activitySummary = $this->transactionLogger->getUserActivitySummary($userId, $period === "monthly" ? 30 : ($period === "weekly" ? 7 : 1));

            // Prepare email data
            $data = [
                "period" => $period,
                "startDate" => $startDate->format("Y-m-d"),
                "endDate" => $endDate->format("Y-m-d"),
                "openTrades" => $openTrades,
                "closedTrades" => $closedTrades,
                "winningTrades" => $winningTrades,
                "losingTrades" => $losingTrades,
                "totalProfit" => $totalProfit,
                "winRate" => $closedTrades > 0 ? round(($winningTrades / $closedTrades) * 100, 2) : 0,
                "activitySummary" => $activitySummary,
                "user" => $user
            ];

            // Log this notification
            $this->transactionLogger->logAction($userId, "notification_sent", [
                "type" => "trading_summary",
                "period" => $period
            ]);

            // Send the email
            Mail::to($user->email)->queue(new AccountSummary($data));

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send trading summary", [
                "user_id" => $userId,
                "error" => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Register a price alert for a user
     * 
     * @param int $userId User ID
     * @param string $symbol Symbol
     * @param string $condition Condition (above, below)
     * @param float $price Target price
     * @return bool Success
     */
    public function registerPriceAlert($userId, $symbol, $condition, $price) {
        try {
            DB::table("price_alerts")->insert([
                "user_id" => $userId,
                "symbol" => $symbol,
                "condition" => $condition,
                "price" => $price,
                "triggered" => false,
                "created_at" => now(),
                "updated_at" => now()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to register price alert", [
                "user_id" => $userId,
                "error" => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check and process price alerts
     * 
     * @param string $symbol Symbol
     * @param float $currentPrice Current price
     * @return int Number of alerts processed
     */
    public function processPriceAlerts($symbol, $currentPrice) {
        $processed = 0;

        try {
            // Get all untriggered alerts for this symbol
            $alerts = DB::table("price_alerts")
                ->where("symbol", $symbol)
                ->where("triggered", false)
                ->get();

            foreach ($alerts as $alert) {
                $shouldTrigger = false;

                // Check if the alert condition is met
                if ($alert->condition === "above" && $currentPrice >= $alert->price) {
                    $shouldTrigger = true;
                } elseif ($alert->condition === "below" && $currentPrice <= $alert->price) {
                    $shouldTrigger = true;
                }

                if ($shouldTrigger) {
                    // Send notification
                    $this->sendPriceAlertNotification(
                        $alert->user_id,
                        $symbol,
                        $alert->condition,
                        $alert->price,
                        $currentPrice
                    );

                    // Mark alert as triggered
                    DB::table("price_alerts")
                        ->where("id", $alert->id)
                        ->update([
                            "triggered" => true,
                            "triggered_at" => now(),
                            "triggered_price" => $currentPrice,
                            "updated_at" => now()
                        ]);

                    $processed++;
                }
            }

            return $processed;
        } catch (\Exception $e) {
            Log::error("Failed to process price alerts", [
                "symbol" => $symbol,
                "error" => $e->getMessage()
            ]);
            return 0;
        }
    }
}
