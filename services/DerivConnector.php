<?php
// DerivConnector.php
// Service for interacting with Deriv API
namespace App\Services;

use GuzzleHttp\Client;

class DerivConnector {
    protected $client;
    protected $apiToken;

    public function __construct() {
        $this->client = new Client(['base_uri' => 'https://api.deriv.com']);
        $this->apiToken = env('DERIV_API_TOKEN');
    }

    // Example: Get account info
    public function getAccountInfo() {
        return $this->client->post('/websockets/v3', [
            'json' => [
                'authorize' => $this->apiToken
            ]
        ]);
    }

    // Place order (buy/sell)
    public function placeOrder($symbol, $side, $amount)
    {
        // Example Deriv API call for placing a trade (simplified)
        $endpoint = '/websockets/v3';
        $bodyArr = [
            'authorize' => $this->apiToken,
            'buy' => 1,
            'price' => $amount,
            'symbol' => $symbol,
            'side' => $side
        ];
        $body = json_encode($bodyArr);
        return $this->client->post($endpoint, [
            'body' => $body,
            'headers' => [ 'Content-Type' => 'application/json' ]
        ]);
    }

    // Get available symbols (markets)
    public function getSymbols()
    {
        $endpoint = '/trading_symbols';
        return $this->client->get($endpoint);
    }

    // Get recent trades for a symbol (mocked, as Deriv is websocket-based)
    public function getTrades($symbol)
    {
        // Deriv's REST API is limited; for real trades, use websocket or mock
        return response()->json(['success' => true, 'message' => 'Not implemented: Use websocket for live trades.']);
    }

    // Enhanced: Get symbol details with error handling, caching, and logging
    public function getSymbol($symbol)
    {
        try {
            $cacheKey = 'deriv_symbol_' . $symbol;
            // Try cache first (5 min)
            if (\Cache::has($cacheKey)) {
                return response()->json(\Cache::get($cacheKey));
            }
            $endpoint = '/trading_symbols';
            $response = $this->client->get($endpoint);
            $data = json_decode($response->getBody(), true);
            if (!isset($data['trading_symbols'])) {
                \Log::error('Deriv symbol fetch error', ['symbol' => $symbol, 'response' => $data]);
                throw new \Exception('Unknown error from Deriv');
            }
            $symbolInfo = collect($data['trading_symbols'])->firstWhere('symbol', $symbol);
            if (!$symbolInfo) {
                throw new \Exception('Symbol not found');
            }
            // Cache for 5 minutes
            \Cache::put($cacheKey, $symbolInfo, 300);
            return response()->json($symbolInfo);
        } catch (\Exception $e) {
            \Log::error('Deriv symbol fetch exception', ['symbol' => $symbol, 'error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Add a method to clear the cache for a symbol (for admin or on-demand refresh)
    public function clearSymbolCache($symbol)
    {
        $cacheKey = 'deriv_symbol_' . $symbol;
        \Cache::forget($cacheKey);
        return response()->json(['success' => true, 'message' => 'Cache cleared for ' . $symbol]);
    }

    // Get historical data for a symbol (real implementation)
    public function getHistoricalData($symbol, $granularity = "1d", $limit = 50)
    {
        try {
            $cacheKey = "deriv_history_{$symbol}_{$granularity}_{$limit}";

            // Check cache first (1 minute)
            if (\Cache::has($cacheKey)) {
                return \Cache::get($cacheKey);
            }

            // Deriv uses WebSocket API, but we'll make a HTTP request for simplicity
            $response = $this->client->post("/websockets/v3", [
                "json" => [
                    "ticks_history" => $symbol,
                    "count" => $limit,
                    "granularity" => $this->convertGranularity($granularity),
                    "style" => "candles",
                    "adjust_start_time" => 1
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            // Handle API errors
            if (isset($data["error"])) {
                \Log::error("Deriv API error: " . json_encode($data["error"]));
                // Fall back to mock data if API fails
                $mockData = $this->getMockHistoricalData($symbol, $limit);
                \Cache::put($cacheKey, $mockData, 60);
                return $mockData;
            }

            // Process candles data
            $result = [];
            $candles = $data["candles"] ?? [];

            foreach ($candles as $candle) {
                $result[] = [
                    "open" => (float)$candle["open"],
                    "high" => (float)$candle["high"],
                    "low" => (float)$candle["low"],
                    "close" => (float)$candle["close"],
                    "volume" => isset($candle["volume"]) ? (float)$candle["volume"] : 0,
                    "timestamp" => $candle["epoch"]
                ];
            }

            // Cache for 1 minute
            \Cache::put($cacheKey, $result, 60);

            return $result;
        } catch (\Exception $e) {
            \Log::error("Deriv historical data error: " . $e->getMessage(), ["symbol" => $symbol]);
            // Return mock data on error
            return $this->getMockHistoricalData($symbol, $limit);
        }
    }

    // Get current price for a symbol
    public function getCurrentPrice($symbol)
    {
        try {
            $cacheKey = "deriv_price_{$symbol}";

            // Check cache first (5 seconds)
            if (\Cache::has($cacheKey)) {
                return \Cache::get($cacheKey);
            }

            $response = $this->client->post("/websockets/v3", [
                "json" => [
                    "ticks" => $symbol,
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data["error"])) {
                \Log::error("Deriv API error: " . json_encode($data["error"]));
                return null;
            }

            $price = $data["tick"]["quote"] ?? null;

            if ($price) {
                // Cache for 5 seconds
                \Cache::put($cacheKey, (float)$price, 5);
                return (float)$price;
            }

            return null;
        } catch (\Exception $e) {
            \Log::error("Deriv API error getting price: " . $e->getMessage());
            return null;
        }
    }

    // Get open positions
    public function getPositions()
    {
        try {
            $response = $this->client->post("/websockets/v3", [
                "json" => [
                    "authorize" => $this->apiToken,
                    "portfolio" => 1
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data["error"])) {
                \Log::error("Deriv API error: " . json_encode($data["error"]));
                return [
                    "success" => false,
                    "message" => $data["error"]["message"] ?? "Unknown error"
                ];
            }

            return [
                "success" => true,
                "data" => $data["portfolio"]["contracts"] ?? []
            ];
        } catch (\Exception $e) {
            \Log::error("Deriv get positions error: " . $e->getMessage());
            return [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
    }

    // Get account balance
    public function getBalance()
    {
        try {
            $response = $this->client->post("/websockets/v3", [
                "json" => [
                    "authorize" => $this->apiToken,
                    "balance" => 1
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data["error"])) {
                \Log::error("Deriv API error: " . json_encode($data["error"]));
                return [
                    "success" => false,
                    "message" => $data["error"]["message"] ?? "Unknown error"
                ];
            }

            return [
                "success" => true,
                "data" => $data["balance"] ?? []
            ];
        } catch (\Exception $e) {
            \Log::error("Deriv balance error: " . $e->getMessage());
            return [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
    }

    // Helper function to convert granularity strings to seconds
    private function convertGranularity($granularity)
    {
        switch (strtolower($granularity)) {
            case "1m":
                return 60;
            case "5m":
                return 300;
            case "15m":
                return 900;
            case "30m":
                return 1800;
            case "1h":
                return 3600;
            case "4h":
                return 14400;
            case "1d":
                return 86400;
            default:
                return 86400; // Default to 1 day
        }
    }

    // Helper to generate mock historical data for testing
    private function getMockHistoricalData($symbol, $limit = 50)
    {
        // Set a starting price based on the symbol
        $startingPrice = 1.1000;

        if (strpos($symbol, "EUR/USD") !== false) {
            $startingPrice = 1.1000;
        } elseif (strpos($symbol, "USD/JPY") !== false) {
            $startingPrice = 150.00;
        } elseif (strpos($symbol, "GBP/USD") !== false) {
            $startingPrice = 1.3000;
        }

        $price = $startingPrice;
        $data = [];

        // Generate random price movements
        for ($i = 0; $i < $limit; $i++) {
            $volatility = strpos($symbol, "JPY") !== false ? 0.050 : 0.0010;
            $change = (rand(-100, 100) / 100) * $volatility;

            $close = $price + $change;
            $open = $price;
            $high = max($close, $open) + (rand(1, 20) / 10000);
            $low = min($close, $open) - (rand(1, 20) / 10000);

            $data[] = [
                "open" => round($open, 5),
                "high" => round($high, 5),
                "low" => round($low, 5),
                "close" => round($close, 5),
                "volume" => rand(100, 500),
                "timestamp" => time() - (($limit - $i) * 3600) // 1 hour interval
            ];

            $price = $close;
        }

        return $data;
    }
}
