<?php
// BotLogic.php
// Core trading bot logic for both Crypto (Kraken) and Forex (Alpaca)
// Handles trend detection, trade management, and user controls

namespace App\Bot;

use Illuminate\Support\Facades\DB;

class BotLogic {
    // Store user bot params (in-memory for demo; use DB in production)
    private $userParams = [];

    // In-memory open positions (for demo)
    protected $openPositions = [];

    // Service connectors
    protected $kucoinConnector;
    protected $derivConnector;
    protected $krakenConnector;
    protected $alpacaConnector;

    // Inject service connectors
    public function __construct(
        \App\Services\KuCoinConnector $kucoinConnector = null,
        \App\Services\DerivConnector $derivConnector = null,
        \App\Services\KrakenConnector $krakenConnector = null,
        \App\Services\AlpacaConnector $alpacaConnector = null
    ) {
        $this->kucoinConnector = $kucoinConnector ?? app(\App\Services\KuCoinConnector::class);
        $this->derivConnector = $derivConnector ?? app(\App\Services\DerivConnector::class);
        $this->krakenConnector = $krakenConnector ?? app(\App\Services\KrakenConnector::class);
        $this->alpacaConnector = $alpacaConnector ?? app(\App\Services\AlpacaConnector::class);
    }

    // Get the appropriate connector for the market
    private function getConnectorForMarket($market) {
        if ($market === 'crypto') {
            // Prioritize KuCoin, fallback to Kraken
            return $this->kucoinConnector ?? $this->krakenConnector;
        } else if ($market === 'forex') {
            // Prioritize Deriv, fallback to Alpaca
            return $this->derivConnector ?? $this->alpacaConnector;
        }

        // Default to KuCoin if market is unknown
        return $this->kucoinConnector;
    }

    // List of major pairs for crypto and forex
    private $majorPairs = [
        'crypto' => [
            'BTC-USDT', 'ETH-USDT', 'BNB-USDT', 'SOL-USDT', 'ADA-USDT',
            'XRP-USDT', 'DOGE-USDT', 'AVAX-USDT', 'MATIC-USDT', 'DOT-USDT'
        ],
        'forex' => [
            'EURUSD', 'USDJPY', 'GBPUSD', 'USDCHF', 'AUDUSD',
            'USDCAD', 'NZDUSD', 'EURJPY', 'GBPJPY', 'EURGBP'
        ]
    ];

    // Check if a pair is in the major pairs list
    private function isMajorPair($market, $symbol) {
        if (!isset($this->majorPairs[$market])) {
            return false;
        }
        return in_array($symbol, $this->majorPairs[$market]);
    }

    // Calculates EMA
    private function calculateEMA($prices, $period) {
        $k = 2 / ($period + 1);
        $ema = $prices[0];
        $emaArr = [$ema];
        for ($i = 1; $i < count($prices); $i++) {
            $ema = $prices[$i] * $k + $ema * (1 - $k);
            $emaArr[] = $ema;
        }
        return $emaArr;
    }

    // Calculates RSI
    private function calculateRSI($prices, $period) {
        $rsiArr = [];
        $gains = 0; $losses = 0;
        for ($i = 1; $i <= $period; $i++) {
            $diff = $prices[$i] - $prices[$i-1];
            if ($diff >= 0) $gains += $diff; else $losses -= $diff;
        }
        $gains /= $period;
        $losses /= $period;
        $rs = $gains / ($losses ?: 1);
        $rsiArr[$period] = 100 - 100 / (1 + $rs);
        for ($i = $period + 1; $i < count($prices); $i++) {
            $diff = $prices[$i] - $prices[$i-1];
            if ($diff >= 0) {
                $gains = ($gains * ($period - 1) + $diff) / $period;
                $losses = ($losses * ($period - 1)) / $period;
            } else {
                $gains = ($gains * ($period - 1)) / $period;
                $losses = ($losses * ($period - 1) - $diff) / $period;
            }
            $rs = $gains / ($losses ?: 1);
            $rsiArr[$i] = 100 - 100 / (1 + $rs);
        }
        return $rsiArr;
    }

    public function getBotAnalytics($userId) {
        $trades = DB::table('bot_trades')->where('user_id', $userId)->get();
        $total = $trades->count();
        $win = $trades->where('profit', '>', 0)->count();
        $loss = $trades->where('profit', '<', 0)->count();
        $profit = $trades->sum('profit');
        return [
            'total' => $total,
            'win' => $win,
            'loss' => $loss,
            'profit' => $profit,
            'winRate' => $total ? round($win / $total * 100, 2) : 0,
            'avgProfit' => $total ? round($profit / $total, 2) : 0,
        ];
    }

    public function updateBotParams($userId, $params) {
        $this->userParams[$userId] = array_merge($this->userParams[$userId] ?? [
            'emaFast' => 7,
            'emaSlow' => 14,
            'rsiPeriod' => 14,
            'riskLevel' => 1
        ], $params);
        return ['success' => true, 'params' => $this->userParams[$userId]];
    }

    public function getUserParams($userId) {
        return $this->userParams[$userId] ?? [
            'emaFast' => 7,
            'emaSlow' => 14,
            'rsiPeriod' => 14,
            'macdFast' => 12,
            'macdSlow' => 26,
            'macdSignal' => 9,
            'riskLevel' => 1,
            'tripleFast' => 5,
            'tripleMid' => 15,
            'tripleSlow' => 30
        ];
    }

    // Enhanced trend detection using selected strategy (EMA/RSI, MACD, Volume)
    public function detectTrend($market, $pairOrSymbol, $historicalData, $userId = null) {
        $params = $userId && isset($this->userParams[$userId]) ? $this->userParams[$userId] : [
            'strategy' => 'ema-rsi',
            'emaFast' => 7,
            'emaSlow' => 14,
            'rsiPeriod' => 14,
            'macdFast' => 12,
            'macdSlow' => 26,
            'macdSignal' => 9,
            'riskLevel' => 1,
            'tripleFast' => 5,
            'tripleMid' => 15,
            'tripleSlow' => 30
        ];
        $closes = array_map(function($p) { return $p['close']; }, $historicalData);
        // Strategy map for extensibility
        $strategy = $params['strategy'] ?? 'ema-rsi';
        if ($strategy === 'macd') {
            if (count($closes) < max($params['macdFast'], $params['macdSlow'], $params['macdSignal'])) return 'hold';
            $emaFast = $this->calculateEMA($closes, $params['macdFast']);
            $emaSlow = $this->calculateEMA($closes, $params['macdSlow']);
            $macd = array_map(function($f, $s) { return $f - $s; }, $emaFast, $emaSlow);
            $signal = $this->calculateEMA($macd, $params['macdSignal']);
            $last = count($closes) - 1;
            if ($macd[$last] > $signal[$last]) return 'buy';
            if ($macd[$last] < $signal[$last]) return 'sell';
            return 'hold';
        } elseif ($strategy === 'volume') {
            if (count($historicalData) < 10) return 'hold';
            $recent = array_slice($historicalData, -5);
            $avgVol = array_sum(array_map(function($p){return $p['volume'] ?? 0;}, array_slice($historicalData, -20))) / 20;
            $lastVol = $recent[count($recent)-1]['volume'] ?? 0;
            if ($lastVol > 1.5 * $avgVol && $recent[count($recent)-1]['close'] > $recent[0]['close']) return 'buy';
            if ($lastVol > 1.5 * $avgVol && $recent[count($recent)-1]['close'] < $recent[0]['close']) return 'sell';
            return 'hold';
        } elseif ($strategy === 'triple-ema') {
            if (count($closes) < max($params['tripleFast'], $params['tripleMid'], $params['tripleSlow'])) return 'hold';
            $emaFast = $this->calculateEMA($closes, $params['tripleFast']);
            $emaMid = $this->calculateEMA($closes, $params['tripleMid']);
            $emaSlow = $this->calculateEMA($closes, $params['tripleSlow']);
            $last = count($closes) - 1;
            if ($emaFast[$last] > $emaMid[$last] && $emaMid[$last] > $emaSlow[$last]) return 'buy';
            if ($emaFast[$last] < $emaMid[$last] && $emaMid[$last] < $emaSlow[$last]) return 'sell';
            return 'hold';
        } else {
            if (count($closes) < max($params['emaFast'], $params['emaSlow'], $params['rsiPeriod'])) return 'hold';
            $emaFast = $this->calculateEMA($closes, $params['emaFast']);
            $emaSlow = $this->calculateEMA($closes, $params['emaSlow']);
            $rsi = $this->calculateRSI($closes, $params['rsiPeriod']);
            $last = count($closes) - 1;
            if ($emaFast[$last] > $emaSlow[$last] && $rsi[$last] < 70 - 10 * ($params['riskLevel'] - 1)) return 'buy';
            if ($emaFast[$last] < $emaSlow[$last] && $rsi[$last] > 30 + 10 * ($params['riskLevel'] - 1)) return 'sell';
            return 'hold';
        }
    }

    // Manage an open trade position, check for profit target or stop loss
    public function manageTrade($userId, $market, $pairOrSymbol, $position, $targetProfit = 0.05, $stopLoss = 0.03) {
        // Get current price from appropriate connector
        $connector = $this->getConnectorForMarket($market);
        $currentPrice = $connector->getCurrentPrice($pairOrSymbol);

        if (!$currentPrice) {
            return [
                'action' => 'error',
                'message' => 'Could not fetch current price',
            ];
        }

        $entryPrice = $position['entry'];
        $side = $position['side'];

        // Calculate profit/loss percentage
        $priceChange = ($currentPrice - $entryPrice) / $entryPrice;
        $profitLoss = $side === 'buy' ? $priceChange : -$priceChange;

        // Check if target profit reached
        if ($profitLoss >= $targetProfit) {
            return [
                'action' => 'close',
                'profitReached' => true,
                'currentPrice' => $currentPrice,
                'profit' => $profitLoss,
                'reason' => 'Target profit reached',
            ];
        }

        // Check if stop loss hit
        if ($profitLoss <= -$stopLoss) {
            return [
                'action' => 'close',
                'profitReached' => false,
                'currentPrice' => $currentPrice,
                'profit' => $profitLoss,
                'reason' => 'Stop loss hit',
            ];
        }

        return [
            'action' => 'hold',
            'profitReached' => false,
            'currentPrice' => $currentPrice,
            'profit' => $profitLoss,
        ];
    }

    // Start trading bot for a user on specific market and symbol
    public function startBot($userId, $market, $symbol) {
        if (!$this->isMajorPair($market, $symbol)) {
            return ['success' => false, 'message' => 'Only major pairs are allowed for trading.'];
        }

        // Check if bot is already running for this user/market/symbol
        $activeBots = $this->getActiveBots();
        foreach ($activeBots as $bot) {
            if ($bot['userId'] == $userId && $bot['market'] == $market && $bot['symbol'] == $symbol) {
                return ['success' => false, 'message' => "Bot is already running for $market on $symbol."];
            }
        }

        // Record in database that bot is active
        DB::table('active_bots')->updateOrInsert(
            ['user_id' => $userId],
            [
                'market' => $market,
                'symbol' => $symbol,
                'status' => 'active',
                'started_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Set initial parameters if not already set
        if (!isset($this->userParams[$userId])) {
            $this->updateBotParams($userId, []);
        }

        // Log the action
        DB::table('audit_logs')->insert([
            'user_id' => $userId,
            'action' => 'bot_start',
            'details' => json_encode(['market' => $market, 'symbol' => $symbol]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['success' => true, 'message' => "Bot started for $market on $symbol."];
    }

    public function stopBot($userId) {
        // Get the bot info first
        $bot = DB::table('active_bots')
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->first();

        if (!$bot) {
            return ['success' => false, 'message' => 'No active bot found for this user.'];
        }

        $market = $bot->market;
        $symbol = $bot->symbol;

        // Check for and close any open positions
        $openPosition = $this->getOpenPosition($userId, $market, $symbol);
        if ($openPosition) {
            // Get current price to close at market
            $connector = $this->getConnectorForMarket($market);
            $currentPrice = $connector->getCurrentPrice($symbol);

            // Close the position
            if ($currentPrice) {
                $this->closePosition($userId, $market, $symbol, ['type' => 'market'], $currentPrice);
            }
        }

        // Update the bot status in database
        DB::table('active_bots')
            ->where('user_id', $userId)
            ->update([
                'status' => 'stopped',
                'stopped_at' => now(),
                'updated_at' => now(),
            ]);

        // Log the action
        DB::table('audit_logs')->insert([
            'user_id' => $userId,
            'action' => 'bot_stop',
            'details' => json_encode(['market' => $market, 'symbol' => $symbol]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['success' => true, 'message' => "Bot stopped for $market on $symbol."];
    }

    public function switchMarket($userId, $newMarket) {
        if (!in_array($newMarket, ['crypto', 'forex'])) {
            return ['success' => false, 'message' => 'Invalid market. Choose crypto or forex.'];
        }

        // Get current bot info
        $bot = DB::table('active_bots')
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->first();

        if (!$bot) {
            return ['success' => false, 'message' => 'No active bot found for this user.'];
        }

        $oldMarket = $bot->market;
        $oldSymbol = $bot->symbol;

        // If already on this market, do nothing
        if ($oldMarket === $newMarket) {
            return ['success' => false, 'message' => "Bot is already trading on $newMarket."];
        }

        // Stop the current bot
        $this->stopBot($userId);

        // Pick a default symbol for the new market
        $newSymbol = $this->majorPairs[$newMarket][0];

        // Start a new bot on the new market
        $result = $this->startBot($userId, $newMarket, $newSymbol);

        if ($result['success']) {
            return ['success' => true, 'message' => "Switched from $oldMarket to $newMarket with $newSymbol."];
        } else {
            return $result;
        }
    }

    // Return all active bots from the database
    public function getActiveBots() {
        $activeBots = DB::table('active_bots')
            ->where('status', 'active')
            ->get();

        $result = [];
        foreach ($activeBots as $bot) {
            $result[] = [
                'userId' => $bot->user_id,
                'market' => $bot->market,
                'symbol' => $bot->symbol,
                'startedAt' => $bot->started_at,
                'updatedAt' => $bot->updated_at
            ];
        }

        return $result;
    }

    // Enhanced analytics for bot performance
    public function getBotAdvancedAnalytics($userId) {
        $trades = DB::table('bot_trades')->where('user_id', $userId)->get();
        $total = $trades->count();
        $win = $trades->where('profit', '>', 0)->count();
        $loss = $trades->where('profit', '<', 0)->count();
        $profit = $trades->sum('profit');
        $holdTimes = $trades->map(function($t){
            if ($t->closed_at && $t->opened_at) {
                return strtotime($t->closed_at) - strtotime($t->opened_at);
            }
            return null;
        })->filter()->all();
        $maxDrawdown = 0; $best = null; $worst = null; $streak = 0; $maxStreak = 0; $currentStreak = 0;
        $peak = 0; $trough = 0; $equity = 0;
        foreach ($trades as $t) {
            $p = $t->profit ?? 0;
            $profit += $p;
            if ($p > 0) { $win++; $currentStreak++; } else if ($p < 0) { $loss++; $currentStreak = 0; }
            if ($currentStreak > $maxStreak) $maxStreak = $currentStreak;
            if ($best === null || $p > $best) $best = $p;
            if ($worst === null || $p < $worst) $worst = $p;
            $equity += $p;
            if ($equity > $peak) $peak = $equity;
            if ($equity < $trough) $trough = $equity;
        }
        $maxDrawdown = $peak - $trough;
        $avgHold = count($holdTimes) ? array_sum($holdTimes) / count($holdTimes) : 0;
        $sharpe = $this->calculateSharpeRatio($trades);
        return [
            'total' => $total,
            'win' => $win,
            'loss' => $loss,
            'profit' => $profit,
            'winRate' => $total ? round($win / $total * 100, 2) : 0,
            'avgProfit' => $total ? round($profit / $total, 2) : 0,
            'maxDrawdown' => $maxDrawdown,
            'bestTrade' => $best,
            'worstTrade' => $worst,
            'maxWinStreak' => $maxStreak,
            'avgHoldTime' => $avgHold,
            'sharpeRatio' => $sharpe,
        ];
    }

    public function getOpenPosition($userId, $market, $symbol) {
        $key = "$userId|$market|$symbol";
        return $this->openPositions[$key] ?? null;
    }
    public function recordOpenPosition($userId, $market, $symbol, $side, $order) {
        $key = "$userId|$market|$symbol";
        $entry = $order['price'] ?? null;
        $size = $order['size'] ?? 0.01;
        $openedAt = now();
        $this->openPositions[$key] = [
            'side' => $side,
            'entry' => $entry,
            'size' => $size,
            'openedAt' => $openedAt,
            'order' => $order
        ];
        // Persist to DB
        DB::table('bot_trades')->insert([
            'user_id' => $userId,
            'market' => $market,
            'symbol' => $symbol,
            'side' => $side,
            'entry' => $entry,
            'size' => $size,
            'opened_at' => $openedAt,
            'entry_order' => json_encode($order),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
    public function closePosition($userId, $market, $symbol, $order, $exitPrice) {
        $key = "$userId|$market|$symbol";
        if (!isset($this->openPositions[$key])) return;
        $position = $this->openPositions[$key];
        $profit = ($exitPrice - $position['entry']) * ($position['side'] === 'buy' ? 1 : -1) * $position['size'];
        $closedAt = now();
        // Update DB
        DB::table('bot_trades')
            ->where('user_id', $userId)
            ->where('market', $market)
            ->where('symbol', $symbol)
            ->whereNull('closed_at')
            ->orderByDesc('opened_at')
            ->limit(1)
            ->update([
                'exit' => $exitPrice,
                'profit' => $profit,
                'closed_at' => $closedAt,
                'exit_order' => json_encode($order),
                'updated_at' => now()
            ]);
        unset($this->openPositions[$key]);
        return $profit;
    }

    // Calculate position size based on user risk and balance
    public function calculatePositionSize($userId, $market, $symbol, $riskLevel = 1, $stopLossPercent = 2) {
        // Get user wallet balance for the appropriate currency
        $currency = ($market === 'crypto') ? 'USDT' : 'USD';

        $wallet = DB::table('user_wallets')
            ->where('user_id', $userId)
            ->where('currency', $currency)
            ->first();

        // Default to small balance if wallet not found
        $balance = $wallet ? $wallet->balance : 1000;

        // Calculate available balance (exclude locked funds)
        $available = $wallet ? ($wallet->balance - $wallet->locked) : $balance;

        // Adjust risk level (1-5 scale)
        $riskPerc = min(max($riskLevel, 1), 5);

        // Calculate risk amount based on available balance
        $riskAmount = ($available * $riskPerc) / 100;

        // Calculate position size based on stop loss percentage
        $positionSize = $riskAmount / ($stopLossPercent / 100);

        // Ensure minimum and maximum position sizes
        if ($market === 'crypto') {
            // For crypto, ensure minimum 0.001 BTC equivalent and max 5% of available balance
            $positionSize = max(min($positionSize, $available * 0.05), 0.001);
        } else {
            // For forex, ensure minimum 0.01 lot and max 2% of available balance
            $positionSize = max(min($positionSize, $available * 0.02), 0.01);
        }

        return $positionSize;
    }

    // Trailing stop logic
    public function isTrailingStopHit($entryPrice, $currentPrice, $trailingPercent, $highestSinceEntry) {
        $trailStop = $highestSinceEntry * (1 - $trailingPercent / 100);
        return $currentPrice <= $trailStop && $currentPrice > $entryPrice;
    }

    // Calculate Sharpe Ratio for trades
    public function calculateSharpeRatio($trades, $riskFreeRate = 0) {
        $returns = array_map(function($t) { return $t->profit ?? 0; }, $trades);
        $n = count($returns);
        if ($n < 2) return 0;
        $avg = array_sum($returns) / $n;
        $std = sqrt(array_sum(array_map(function($r) use ($avg) { return pow($r - $avg, 2); }, $returns)) / ($n - 1));
        if ($std == 0) return 0;
        return ($avg - $riskFreeRate) / $std;
    }

    // Before opening a new trade, check for open position
    public function canOpenTrade($userId, $market, $symbol) {
        $key = "$userId|$market|$symbol";
        return !isset($this->openPositions[$key]);
    }
}
