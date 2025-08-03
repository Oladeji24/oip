<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\KuCoinConnector;
use App\Services\KrakenConnector;
use App\Services\AlpacaConnector;
use App\Services\DerivConnector;
use App\Bot\BotLogic;
use App\Services\TransactionLogger;
use App\Services\BacktestService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TradingController extends Controller
{
    protected $botLogic;
    protected $transactionLogger;
    protected $backtestService;

    public function __construct(
        BotLogic $botLogic, 
        TransactionLogger $transactionLogger,
        BacktestService $backtestService
    ) {
        $this->botLogic = $botLogic;
        $this->transactionLogger = $transactionLogger;
        $this->backtestService = $backtestService;
    }

    /**
     * Get user dashboard data
     */
    public function getDashboard()
    {
        $user = Auth::user();
        $userId = $user->id;

        // Get wallet balances
        $wallets = DB::table("user_wallets")
            ->where("user_id", $userId)
            ->get();

        // Get active bots
        $bots = DB::table("active_bots")
            ->where("user_id", $userId)
            ->get();

        // Get recent trades
        $trades = DB::table("bot_trades")
            ->where("user_id", $userId)
            ->orderBy("created_at", "desc")
            ->limit(10)
            ->get();

        // Get account activity summary
        $activitySummary = $this->transactionLogger->getUserActivitySummary($userId, 30);

        return response()->json([
            "wallets" => $wallets,
            "bots" => $bots,
            "recent_trades" => $trades,
            "activity_summary" => $activitySummary
        ]);
    }

    /**
     * Get available markets and symbols
     */
    public function getMarkets(Request $request)
    {
        $market = $request->input("market", "crypto");

        $symbols = [];
        $connector = null;

        switch ($market) {
            case "crypto":
                $connector = app(KuCoinConnector::class);
                break;
            case "forex":
                $connector = app(DerivConnector::class);
                break;
            default:
                $connector = app(KuCoinConnector::class);
        }

        try {
            $symbols = $connector->getAvailableSymbols();
        } catch (\Exception $e) {
            return response()->json([
                "error" => "Failed to get symbols: " . $e->getMessage()
            ], 500);
        }

        return response()->json([
            "market" => $market,
            "symbols" => $symbols
        ]);
    }

    /**
     * Get current price for a symbol
     */
    public function getPrice(Request $request)
    {
        $market = $request->input("market", "crypto");
        $symbol = $request->input("symbol");

        if (!$symbol) {
            return response()->json([
                "error" => "Symbol is required"
            ], 400);
        }

        $connector = null;
        switch ($market) {
            case "crypto":
                $connector = app(KuCoinConnector::class);
                break;
            case "forex":
                $connector = app(DerivConnector::class);
                break;
            default:
                $connector = app(KuCoinConnector::class);
        }

        try {
            $price = $connector->getCurrentPrice($symbol);
            return response()->json([
                "symbol" => $symbol,
                "price" => $price,
                "market" => $market,
                "timestamp" => time()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "error" => "Failed to get price: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start a new bot
     */
    public function startBot(Request $request)
    {
        $user = Auth::user();
        $userId = $user->id;

        $market = $request->input("market");
        $symbol = $request->input("symbol");
        $strategy = $request->input("strategy", "ema-rsi");
        $params = $request->input("params", []);
        $initialCapital = $request->input("capital", 100);

        if (!$market || !$symbol) {
            return response()->json([
                "error" => "Market and symbol are required"
            ], 400);
        }

        // Check if user has sufficient balance
        $balance = DB::table("user_wallets")
            ->where("user_id", $userId)
            ->where("currency", "USD") // Assuming USD is the base currency
            ->value("balance");

        if (!$balance || $balance < $initialCapital) {
            return response()->json([
                "error" => "Insufficient balance"
            ], 400);
        }

        // Check if bot already exists
        $existingBot = DB::table("active_bots")
            ->where("user_id", $userId)
            ->where("market", $market)
            ->where("symbol", $symbol)
            ->first();

        if ($existingBot) {
            return response()->json([
                "error" => "Bot already exists for this symbol"
            ], 400);
        }

        // Create bot
        $botId = DB::table("active_bots")->insertGetId([
            "user_id" => $userId,
            "market" => $market,
            "symbol" => $symbol,
            "strategy" => $strategy,
            "parameters" => json_encode($params),
            "status" => "active",
            "initial_capital" => $initialCapital,
            "current_capital" => $initialCapital,
            "created_at" => now(),
            "updated_at" => now()
        ]);

        // Lock funds
        DB::table("user_wallets")
            ->where("user_id", $userId)
            ->where("currency", "USD")
            ->update([
                "balance" => DB::raw("balance - {$initialCapital}"),
                "locked" => DB::raw("locked + {$initialCapital}")
            ]);

        // Log action
        $this->transactionLogger->logAction($userId, "bot_started", [
            "bot_id" => $botId,
            "market" => $market,
            "symbol" => $symbol,
            "strategy" => $strategy,
            "initial_capital" => $initialCapital
        ]);

        return response()->json([
            "success" => true,
            "bot_id" => $botId,
            "message" => "Bot started successfully"
        ]);
    }

    /**
     * Stop a bot
     */
    public function stopBot(Request $request)
    {
        $user = Auth::user();
        $userId = $user->id;

        $botId = $request->input("bot_id");

        if (!$botId) {
            return response()->json([
                "error" => "Bot ID is required"
            ], 400);
        }

        // Get bot info
        $bot = DB::table("active_bots")
            ->where("id", $botId)
            ->where("user_id", $userId)
            ->first();

        if (!$bot) {
            return response()->json([
                "error" => "Bot not found"
            ], 404);
        }

        // Update bot status
        DB::table("active_bots")
            ->where("id", $botId)
            ->update([
                "status" => "stopped",
                "updated_at" => now()
            ]);

        // Release locked funds
        DB::table("user_wallets")
            ->where("user_id", $userId)
            ->where("currency", "USD")
            ->update([
                "balance" => DB::raw("balance + {$bot->current_capital}"),
                "locked" => DB::raw("locked - {$bot->initial_capital}")
            ]);

        // Log action
        $this->transactionLogger->logAction($userId, "bot_stopped", [
            "bot_id" => $botId,
            "market" => $bot->market,
            "symbol" => $bot->symbol,
            "initial_capital" => $bot->initial_capital,
            "final_capital" => $bot->current_capital,
            "profit" => $bot->current_capital - $bot->initial_capital
        ]);

        return response()->json([
            "success" => true,
            "message" => "Bot stopped successfully",
            "profit" => $bot->current_capital - $bot->initial_capital
        ]);
    }

    /**
     * Run backtest on historical data
     */
    public function runBacktest(Request $request)
    {
        $user = Auth::user();
        $userId = $user->id;

        $market = $request->input("market");
        $symbol = $request->input("symbol");
        $strategy = $request->input("strategy", "ema-rsi");
        $params = $request->input("params", []);
        $startDate = $request->input("start_date");
        $endDate = $request->input("end_date");
        $initialCapital = $request->input("capital", 10000);

        if (!$market || !$symbol) {
            return response()->json([
                "error" => "Market and symbol are required"
            ], 400);
        }

        // Add strategy to params
        $params["strategy"] = $strategy;

        try {
            // Run backtest
            $results = $this->backtestService->runBacktest(
                $market,
                $symbol,
                $params,
                $startDate,
                $endDate,
                $initialCapital
            );

            // Log action
            $this->transactionLogger->logAction($userId, "backtest_run", [
                "market" => $market,
                "symbol" => $symbol,
                "strategy" => $strategy,
                "start_date" => $startDate,
                "end_date" => $endDate,
                "initial_capital" => $initialCapital,
                "final_capital" => $results["finalCapital"],
                "profit" => $results["netProfit"]
            ]);

            return response()->json($results);
        } catch (\Exception $e) {
            return response()->json([
                "error" => "Backtest failed: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user trades
     */
    public function getTrades(Request $request)
    {
        $user = Auth::user();
        $userId = $user->id;

        $limit = $request->input("limit", 50);
        $offset = $request->input("offset", 0);

        $trades = DB::table("bot_trades")
            ->where("user_id", $userId)
            ->orderBy("created_at", "desc")
            ->offset($offset)
            ->limit($limit)
            ->get();

        $total = DB::table("bot_trades")
            ->where("user_id", $userId)
            ->count();

        return response()->json([
            "trades" => $trades,
            "total" => $total,
            "offset" => $offset,
            "limit" => $limit
        ]);
    }
}
