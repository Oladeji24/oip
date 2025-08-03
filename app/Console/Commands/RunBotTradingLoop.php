<?php
// app/Console/Commands/RunBotTradingLoop.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Bot\BotLogic;
use App\Services\KuCoinConnector;
use App\Services\DerivConnector;
use App\Services\TransactionLogger;

class RunBotTradingLoop extends Command
{
    protected $signature = 'bot:run-loop';
    protected $description = 'Run trading bot loop for all active users';

    public function handle()
    {
        $bot = app(BotLogic::class);
        $kucoin = app(KuCoinConnector::class);
        $deriv = app(DerivConnector::class);
        $logger = app(TransactionLogger::class);
        $activeBots = $bot->getActiveBots(); // [{userId, market, symbol}]
        foreach ($activeBots as $active) {
            $userId = $active['userId'];
            $market = $active['market'];
            $symbol = $active['symbol'];
            // Fetch historical data
            if ($market === 'crypto') {
                $history = $kucoin->getHistoricalData($symbol, 50); // [{close: ...}]
            } else {
                $history = $deriv->getHistoricalData($symbol, 50);
            }
            if (!$history || count($history) < 20) continue;
            $signal = $bot->detectTrend($market, $symbol, $history, $userId);
            // --- Real trade execution logic ---
            $position = $bot->getOpenPosition($userId, $market, $symbol); // null or array
            $params = $bot->getUserParams($userId);
            $riskLevel = $params['riskLevel'] ?? 1;
            $size = $bot->calculatePositionSize($userId, $market, $symbol, $riskLevel, 2); // 2% stop loss
            if (!$position && ($signal === 'buy' || $signal === 'sell')) {
                $side = $signal;
                if ($market === 'crypto') {
                    $order = $kucoin->placeOrder($symbol, $side, 'market', $size);
                } else {
                    $order = $deriv->placeOrder($symbol, $side, $size);
                }
                $bot->recordOpenPosition($userId, $market, $symbol, $side, $order);
                $logger->logBotAction($userId, $market, $symbol, "Opened $side", $order);
            } elseif ($position) {
                $entry = $position['entry'] ?? null;
                $current = $history[count($history)-1]['close'];
                $highest = max(array_map(function($h){return $h['close'];}, $history));
                $shouldExit = $bot->shouldExitPosition($position, $current);
                // Trailing stop: exit if hit
                $trailingStopHit = $bot->isTrailingStopHit($entry, $current, 2, $highest); // 2% trailing
                if ($shouldExit || $trailingStopHit) {
                    $side = $position['side'] === 'buy' ? 'sell' : 'buy';
                    if ($market === 'crypto') {
                        $order = $kucoin->placeOrder($symbol, $side, 'market', $position['size']);
                    } else {
                        $order = $deriv->placeOrder($symbol, $side, $position['size']);
                    }
                    $bot->closePosition($userId, $market, $symbol, $order, $current);
                    $logger->logBotAction($userId, $market, $symbol, "Closed $side", $order);
                }
            }
        }
        $this->info('Bot trading loop executed.');
    }
}
