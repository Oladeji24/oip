<?php
// KuCoinConnector.php
// Service for interacting with KuCoin API
namespace App\Services;

use GuzzleHttp\Client;

class KuCoinConnector {
    protected $client;
    protected $apiKey;
    protected $apiSecret;
    protected $apiPassphrase;

    public function __construct() {
        $this->client = new Client(['base_uri' => 'https://api.kucoin.com']);
        $this->apiKey = env('KUCOIN_API_KEY');
        $this->apiSecret = env('KUCOIN_API_SECRET');
        $this->apiPassphrase = env('KUCOIN_API_PASSPHRASE');
    }

    // Example: Get account balances
    public function getBalances() {
        // TODO: Implement KuCoin API authentication and request signing
        // See https://docs.kucoin.com/#api-key-authentication
        return $this->client->get('/api/v1/accounts', [
            'headers' => $this->getAuthHeaders('GET', '/api/v1/accounts', ''),
        ]);
    }

    // Place order (buy/sell/stop-limit)
    public function placeOrder($symbol, $side, $type = 'market', $size, $price = null, $stop = null, $stopPrice = null)
    {
        $endpoint = '/api/v1/orders';
        $bodyArr = [
            'symbol' => $symbol,
            'side' => $side,
            'type' => $type,
            'size' => $size
        ];
        if ($type === 'limit' && $price) {
            $bodyArr['price'] = $price;
        }
        if ($type === 'stop_limit' && $price && $stop && $stopPrice) {
            $bodyArr['price'] = $price;
            $bodyArr['stop'] = $stop; // e.g. 'entry' or 'loss'
            $bodyArr['stopPrice'] = $stopPrice;
        }
        $body = json_encode($bodyArr);
        return $this->client->post($endpoint, [
            'headers' => $this->getAuthHeaders('POST', $endpoint, $body),
            'body' => $body
        ]);
    }

    // Get market ticker for a symbol
    public function getTicker($symbol)
    {
        $endpoint = '/api/v1/market/orderbook/level1?symbol=' . $symbol;
        return $this->client->get($endpoint);
    }

    // Get order book (level 2) for a symbol
    public function getOrderBook($symbol)
    {
        $endpoint = '/api/v1/market/orderbook/level2_20?symbol=' . $symbol;
        return $this->client->get($endpoint);
    }

    // Get available symbols (markets)
    public function getSymbols()
    {
        $endpoint = '/api/v1/symbols';
        return $this->client->get($endpoint);
    }

    // Get recent trades for a symbol
    public function getTrades($symbol)
    {
        $endpoint = '/api/v1/market/histories?symbol=' . $symbol;
        return $this->client->get($endpoint);
    }

    // Enhanced: Get symbol details with error handling, caching, and logging
    public function getSymbol($symbol)
    {
        try {
            $cacheKey = 'kucoin_symbol_' . $symbol;
            // Try cache first (5 min)
            if (\Cache::has($cacheKey)) {
                return response()->json(\Cache::get($cacheKey));
            }
            $endpoint = '/api/v1/symbols/' . $symbol;
            $response = $this->client->get($endpoint);
            $data = json_decode($response->getBody(), true);
            if (isset($data['code']) && $data['code'] !== '200000') {
                \Log::error('KuCoin symbol fetch error', ['symbol' => $symbol, 'response' => $data]);
                throw new \Exception($data['msg'] ?? 'Unknown error from KuCoin');
            }
            // Cache for 5 minutes
            \Cache::put($cacheKey, $data, 300);
            return response()->json($data);
        } catch (\Exception $e) {
            \Log::error('KuCoin symbol fetch exception', ['symbol' => $symbol, 'error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Add a method to clear the cache for a symbol (for admin or on-demand refresh)
    public function clearSymbolCache($symbol)
    {
        $cacheKey = 'kucoin_symbol_' . $symbol;
        \Cache::forget($cacheKey);
        return response()->json(['success' => true, 'message' => 'Cache cleared for ' . $symbol]);
    }

    // Get historical data for a symbol (real implementation)
    public function getHistoricalData($symbol, $type = "1hour", $limit = 50)
    {
        try {
            $cacheKey = "kucoin_history_{$symbol}_{$type}_{$limit}";

            // Check cache first (1 minute)
            if (\Cache::has($cacheKey)) {
                return \Cache::get($cacheKey);
            }

            // Convert to KuCoin expected format
            $typeMap = [
                "1min" => "1min",
                "5min" => "5min", 
                "15min" => "15min", 
                "30min" => "30min",
                "1hour" => "1hour", 
                "4hour" => "4hour",
                "1day" => "1day",
                "1week" => "1week"
            ];

            $kucoinType = $typeMap[$type] ?? "1hour";

            // Make API request
            $endpoint = "/api/v1/market/candles?symbol={$symbol}&type={$kucoinType}&limit={$limit}";
            $response = $this->client->get($endpoint);
            $data = json_decode($response->getBody(), true);

            if (!isset($data["data"]) || !is_array($data["data"])) {
                \Log::error("KuCoin historical data error", [
                    "symbol" => $symbol, 
                    "response" => $data
                ]);

                // Fall back to mock data
                $mockData = $this->getMockHistoricalData($symbol, $limit);
                \Cache::put($cacheKey, $mockData, 60);
                return $mockData;
            }

            // Format the data for our system
            // KuCoin format: [timestamp, open, close, high, low, volume, turnover]
            $result = [];
            foreach ($data["data"] as $candle) {
                $result[] = [
                    "timestamp" => (int)$candle[0],
                    "open" => (float)$candle[1],
                    "close" => (float)$candle[2],
                    "high" => (float)$candle[3],
                    "low" => (float)$candle[4],
                    "volume" => (float)$candle[5]
                ];
            }

            // Cache for 1 minute
            \Cache::put($cacheKey, $result, 60);

            return $result;
        } catch (\Exception $e) {
            \Log::error("KuCoin historical data exception", [
                "symbol" => $symbol, 
                "error" => $e->getMessage()
            ]);

            // Return mock data on error
            return $this->getMockHistoricalData($symbol, $limit);
        }
    }

    /**
     * Get current price for a symbol
     * 
     * @param string $symbol Symbol e.g. "BTC-USDT"
     * @return float|null Current price or null if error
     */
    public function getCurrentPrice($symbol)
    {
        try {
            $cacheKey = "kucoin_price_{$symbol}";

            // Check cache first (5 seconds)
            if (\Cache::has($cacheKey)) {
                return \Cache::get($cacheKey);
            }

            $endpoint = "/api/v1/market/orderbook/level1?symbol={$symbol}";
            $response = $this->client->get($endpoint);
            $data = json_decode($response->getBody(), true);

            if (!isset($data["data"]) || !isset($data["data"]["price"])) {
                \Log::error("KuCoin price fetch error", [
                    "symbol" => $symbol, 
                    "response" => $data
                ]);
                return null;
            }

            $price = (float)$data["data"]["price"];

            // Cache for 5 seconds
            \Cache::put($cacheKey, $price, 5);

            return $price;
        } catch (\Exception $e) {
            \Log::error("KuCoin price fetch exception", [
                "symbol" => $symbol, 
                "error" => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Generate mock historical data for testing
     *
     * @param string $symbol Symbol e.g. "BTC-USDT"
     * @param int $limit Number of candles to generate
     * @return array Array of candle data
     */
    private function getMockHistoricalData($symbol, $limit = 50)
    {
        // Set starting price based on symbol
        $startingPrice = 50000; // Default BTC price

        if (strpos($symbol, "BTC") !== false) {
            $startingPrice = 50000;
        } elseif (strpos($symbol, "ETH") !== false) {
            $startingPrice = 3000;
        } elseif (strpos($symbol, "SOL") !== false) {
            $startingPrice = 100;
        }

        $price = $startingPrice;
        $data = [];
        $timestamp = time();

        // Generate random price movements
        for ($i = 0; $i < $limit; $i++) {
            $volatility = $startingPrice * 0.02; // 2% volatility
            $change = (mt_rand(-100, 100) / 100) * $volatility;

            $close = $price + $change;
            $open = $price;
            $high = max($close, $open) + (mt_rand(1, 20) / 1000) * $startingPrice;
            $low = min($close, $open) - (mt_rand(1, 20) / 1000) * $startingPrice;
            $volume = mt_rand(5, 100) / 10;

            // Earlier timestamps for older candles
            $candle_timestamp = $timestamp - ($limit - $i) * 3600;

            $data[] = [
                "timestamp" => $candle_timestamp,
                "open" => round($open, 2),
                "close" => round($close, 2),
                "high" => round($high, 2),
                "low" => round($low, 2),
                "volume" => round($volume, 4)
            ];

            $price = $close;
        }

        return $data;
    }

    private function getAuthHeaders($method, $endpoint, $body) {
        $timestamp = (string)(int)(microtime(true) * 1000);
        $strForSign = $timestamp . strtoupper($method) . $endpoint . $body;
        $signature = base64_encode(hash_hmac('sha256', $strForSign, $this->apiSecret, true));
        $passphrase = base64_encode(hash_hmac('sha256', $this->apiPassphrase, $this->apiSecret, true));
        return [
            'KC-API-KEY' => $this->apiKey,
            'KC-API-SIGN' => $signature,
            'KC-API-TIMESTAMP' => $timestamp,
            'KC-API-PASSPHRASE' => $passphrase,
            'KC-API-KEY-VERSION' => '2',
            'Content-Type' => 'application/json',
        ];
    }
}
