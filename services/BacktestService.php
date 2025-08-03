<?php
// BacktestService.php
// Service for running trading strategies against historical data

namespace App\Services;

use App\Bot\BotLogic;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BacktestService {
    protected $botLogic;
    protected $kucoinConnector;
    protected $derivConnector;
    protected $krakenConnector;
    protected $alpacaConnector;

    public function __construct(
        BotLogic $botLogic,
        KuCoinConnector $kucoinConnector = null,
        DerivConnector $derivConnector = null,
        KrakenConnector $krakenConnector = null,
        AlpacaConnector $alpacaConnector = null
    ) {
        $this->botLogic = $botLogic;
        $this->kucoinConnector = $kucoinConnector ?? app(KuCoinConnector::class);
        $this->derivConnector = $derivConnector ?? app(DerivConnector::class);
        $this->krakenConnector = $krakenConnector ?? app(KrakenConnector::class);
        $this->alpacaConnector = $alpacaConnector ?? app(AlpacaConnector::class);
    }

    /**
     * Run a backtest for a specific trading strategy
     * 
     * @param string $market "crypto" or "forex"
     * @param string $symbol Symbol to test
     * @param array $params Strategy parameters
     * @param string $startDate Start date in Y-m-d format
     * @param string $endDate End date in Y-m-d format
     * @param float $initialCapital Starting capital
     * @return array Backtest results
     */
    public function runBacktest($market, $symbol, $params = [], $startDate = null, $endDate = null, $initialCapital = 10000) {
        // Default to last 30 days if no dates provided
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now();
        $startDate = $startDate ? Carbon::parse($startDate) : $endDate->copy()->subDays(30);

        // Get historical data based on market
        $historicalData = [];
        if ($market === "crypto") {
            // Use KuCoin by default for crypto
            $connector = $this->kucoinConnector;
            $granularity = "1day"; // Daily candles
            $historicalData = $connector->getHistoricalData($symbol, $granularity, 1000);
        } else {
            // Use Deriv by default for forex
            $connector = $this->derivConnector;
            $granularity = "1d"; // Daily candles
            $historicalData = $connector->getHistoricalData($symbol, $granularity, 1000);
        }

        // Filter data by date range
        $startTimestamp = $startDate->timestamp;
        $endTimestamp = $endDate->timestamp;
        $filteredData = array_filter($historicalData, function($candle) use ($startTimestamp, $endTimestamp) {
            $candleTime = $candle["timestamp"] ?? time();
            return $candleTime >= $startTimestamp && $candleTime <= $endTimestamp;
        });

        // Sort by timestamp (oldest first)
        usort($filteredData, function($a, $b) {
            return ($a["timestamp"] ?? 0) - ($b["timestamp"] ?? 0);
        });

        // Initialize backtest variables
        $balance = $initialCapital;
        $equity = $initialCapital;
        $trades = [];
        $positions = [];
        $currentPosition = null;

        // Process candles one by one
        $equityCurve = [["timestamp" => $startTimestamp, "equity" => $initialCapital]];

        // Set default bot parameters if not provided
        $botParams = array_merge([
            "strategy" => "ema-rsi",
            "emaFast" => 7,
            "emaSlow" => 14,
            "rsiPeriod" => 14,
            "macdFast" => 12,
            "macdSlow" => 26,
            "macdSignal" => 9,
            "riskLevel" => 1,
            "tripleFast" => 5,
            "tripleMid" => 15,
            "tripleSlow" => 30
        ], $params);

        // Minimum number of candles needed before we can start trading
        $warmupPeriod = max(
            $botParams["emaSlow"],
            $botParams["rsiPeriod"],
            $botParams["macdSlow"],
            $botParams["macdSignal"],
            $botParams["tripleSlow"]
        );

        // Process each candle for trading decisions
        for ($i = $warmupPeriod; $i < count($filteredData); $i++) {
            $candle = $filteredData[$i];
            $lookback = array_slice($filteredData, 0, $i + 1);
            $timestamp = $candle["timestamp"] ?? time();

            // Detect trend using current parameters
            $signal = $this->botLogic->detectTrend($market, $symbol, $lookback, null, $botParams);

            // Check if we have an open position
            if ($currentPosition) {
                // Calculate current profit/loss
                $entryPrice = $currentPosition["entry"];
                $currentPrice = $candle["close"];
                $side = $currentPosition["side"];
                $size = $currentPosition["size"];

                $priceDiff = $currentPrice - $entryPrice;
                $profitPercent = ($side === "buy") ? $priceDiff / $entryPrice : -$priceDiff / $entryPrice;
                $profitAmount = $currentPosition["value"] * $profitPercent;

                // Update equity curve
                $equity = $balance + $profitAmount;
                $equityCurve[] = ["timestamp" => $timestamp, "equity" => $equity];

                // Close position if signal is opposite or take profit/stop loss hit
                $shouldClose = false;

                if (
                    ($side === "buy" && $signal === "sell") ||
                    ($side === "sell" && $signal === "buy") ||
                    ($profitPercent >= 0.05) || // 5% take profit
                    ($profitPercent <= -0.03)   // 3% stop loss
                ) {
                    $shouldClose = true;
                }

                if ($shouldClose) {
                    // Close the position
                    $balance = $equity;

                    // Record the trade
                    $currentPosition["exit"] = $currentPrice;
                    $currentPosition["exitTime"] = $timestamp;
                    $currentPosition["profit"] = $profitAmount;
                    $currentPosition["profitPercent"] = $profitPercent;

                    $trades[] = $currentPosition;
                    $currentPosition = null;
                }
            } else {
                // No open position, check if we should enter
                if ($signal === "buy" || $signal === "sell") {
                    // Calculate position size (2% risk per trade)
                    $riskAmount = $balance * 0.02;
                    $stopLossPercent = 0.03; // 3% stop loss
                    $positionSize = $riskAmount / $stopLossPercent;

                    // Ensure we don't use more than 20% of balance for a single trade
                    $maxSize = $balance * 0.2;
                    $positionSize = min($positionSize, $maxSize);

                    // Open a new position
                    $currentPosition = [
                        "side" => $signal,
                        "entry" => $candle["close"],
                        "size" => $positionSize,
                        "value" => $positionSize,
                        "time" => $timestamp,
                        "symbol" => $symbol
                    ];

                    // Add to positions array
                    $positions[] = $currentPosition;
                }

                // Update equity curve (no open position)
                $equityCurve[] = ["timestamp" => $timestamp, "equity" => $balance];
            }
        }

        // Close any remaining position at the last price
        if ($currentPosition) {
            $lastCandle = end($filteredData);
            $lastPrice = $lastCandle["close"];

            $entryPrice = $currentPosition["entry"];
            $side = $currentPosition["side"];

            $priceDiff = $lastPrice - $entryPrice;
            $profitPercent = ($side === "buy") ? $priceDiff / $entryPrice : -$priceDiff / $entryPrice;
            $profitAmount = $currentPosition["value"] * $profitPercent;

            $balance += $profitAmount;

            // Record the trade
            $currentPosition["exit"] = $lastPrice;
            $currentPosition["exitTime"] = $lastCandle["timestamp"] ?? time();
            $currentPosition["profit"] = $profitAmount;
            $currentPosition["profitPercent"] = $profitPercent;

            $trades[] = $currentPosition;
        }

        // Calculate performance metrics
        $totalTrades = count($trades);
        $winningTrades = 0;
        $losingTrades = 0;
        $totalProfit = 0;
        $totalLoss = 0;
        $largestWin = 0;
        $largestLoss = 0;

        foreach ($trades as $trade) {
            $profit = $trade["profit"];

            if ($profit > 0) {
                $winningTrades++;
                $totalProfit += $profit;
                $largestWin = max($largestWin, $profit);
            } else {
                $losingTrades++;
                $totalLoss += abs($profit);
                $largestLoss = max($largestLoss, abs($profit));
            }
        }

        $winRate = $totalTrades > 0 ? ($winningTrades / $totalTrades) * 100 : 0;
        $profitFactor = $totalLoss > 0 ? $totalProfit / $totalLoss : ($totalProfit > 0 ? 999 : 0);
        $netProfit = $totalProfit - $totalLoss;
        $returnOnInvestment = ($netProfit / $initialCapital) * 100;

        // Calculate drawdown
        $maxEquity = $initialCapital;
        $maxDrawdown = 0;
        $currentDrawdown = 0;

        foreach ($equityCurve as $point) {
            $equity = $point["equity"];

            if ($equity > $maxEquity) {
                $maxEquity = $equity;
                $currentDrawdown = 0;
            } else {
                $currentDrawdown = ($maxEquity - $equity) / $maxEquity * 100;
                $maxDrawdown = max($maxDrawdown, $currentDrawdown);
            }
        }

        // Prepare results
        $results = [
            "symbol" => $symbol,
            "market" => $market,
            "strategy" => $botParams["strategy"],
            "parameters" => $botParams,
            "startDate" => $startDate->format("Y-m-d"),
            "endDate" => $endDate->format("Y-m-d"),
            "initialCapital" => $initialCapital,
            "finalCapital" => $balance,
            "netProfit" => $netProfit,
            "returnOnInvestment" => round($returnOnInvestment, 2),
            "totalTrades" => $totalTrades,
            "winningTrades" => $winningTrades,
            "losingTrades" => $losingTrades,
            "winRate" => round($winRate, 2),
            "profitFactor" => round($profitFactor, 2),
            "largestWin" => $largestWin,
            "largestLoss" => $largestLoss,
            "maxDrawdown" => round($maxDrawdown, 2),
            "sharpeRatio" => $this->calculateSharpeRatio($equityCurve),
            "equityCurve" => $equityCurve,
            "trades" => $trades
        ];

        return $results;
    }

    /**
     * Calculate Sharpe Ratio from equity curve
     */
    private function calculateSharpeRatio($equityCurve) {
        if (count($equityCurve) < 2) {
            return 0;
        }

        $returns = [];
        for ($i = 1; $i < count($equityCurve); $i++) {
            $prev = $equityCurve[$i-1]["equity"];
            $current = $equityCurve[$i]["equity"];
            $returns[] = ($current - $prev) / $prev;
        }

        $avgReturn = array_sum($returns) / count($returns);

        $variance = 0;
        foreach ($returns as $return) {
            $variance += pow($return - $avgReturn, 2);
        }
        $stdDev = sqrt($variance / count($returns));

        return $stdDev > 0 ? ($avgReturn / $stdDev) * sqrt(252) : 0; // Annualized
    }

    /**
     * Optimize strategy parameters using grid search
     */
    public function optimizeStrategy($market, $symbol, $startDate = null, $endDate = null, $initialCapital = 10000) {
        // Define parameter ranges to test
        $paramRanges = [
            "emaFast" => [5, 7, 9, 12],
            "emaSlow" => [14, 21, 30],
            "rsiPeriod" => [9, 14, 21],
            "riskLevel" => [1, 2, 3]
        ];

        $bestResult = null;
        $bestPerformance = -999999;

        // Simple implementation - in real-world would use more sophisticated approach
        foreach ($paramRanges["emaFast"] as $emaFast) {
            foreach ($paramRanges["emaSlow"] as $emaSlow) {
                // Skip invalid combinations
                if ($emaFast >= $emaSlow) continue;

                foreach ($paramRanges["rsiPeriod"] as $rsiPeriod) {
                    foreach ($paramRanges["riskLevel"] as $riskLevel) {
                        $params = [
                            "strategy" => "ema-rsi",
                            "emaFast" => $emaFast,
                            "emaSlow" => $emaSlow,
                            "rsiPeriod" => $rsiPeriod,
                            "riskLevel" => $riskLevel
                        ];

                        // Run backtest with these parameters
                        $result = $this->runBacktest(
                            $market, 
                            $symbol, 
                            $params, 
                            $startDate, 
                            $endDate, 
                            $initialCapital
                        );

                        // Calculate performance score (combination of return and risk metrics)
                        $performance = ($result["returnOnInvestment"] * $result["winRate"] / 100) - ($result["maxDrawdown"] * 2);

                        if ($performance > $bestPerformance) {
                            $bestPerformance = $performance;
                            $bestResult = $result;
                        }
                    }
                }
            }
        }

        return $bestResult;
    }
}
