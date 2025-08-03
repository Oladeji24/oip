<?php
// AlpacaConnector.php
// Service for interacting with Alpaca API for Forex/Stock trading

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AlpacaConnector {
    protected $apiKey;
    protected $apiSecret;
    protected $client;
    protected $baseUrl = "https://paper-api.alpaca.markets"; // Use live endpoint for production
    protected $paperTrading = true;

    public function __construct() {
        $this->paperTrading = env("ALPACA_PAPER_TRADING", true);
        $this->baseUrl = $this->paperTrading 
            ? "https://paper-api.alpaca.markets" 
            : "https://api.alpaca.markets";

        $this->apiKey = env("ALPACA_API_KEY");
        $this->apiSecret = env("ALPACA_API_SECRET");

        $this->client = new Client([
            "base_uri" => $this->baseUrl,
            "headers" => [
                "Accept" => "application/json",
                "Content-Type" => "application/json",
                "APCA-API-KEY-ID" => $this->apiKey,
                "APCA-API-SECRET-KEY" => $this->apiSecret
            ]
        ]);
    }

    // Get account information
    public function getAccount() {
        try {
            $response = $this->client->get("/v2/account");
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error("Alpaca API error: " . $e->getMessage());
            return [
                "success" => false, 
                "message" => $e->getMessage()
            ];
        }
    }

    // Get current price for a symbol
    public function getCurrentPrice($symbol) {
        try {
            $cacheKey = "alpaca_price_" . $symbol;

            // Check cache first (10 seconds)
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            $response = $this->client->get("/v2/last/stocks/{$symbol}");
            $data = json_decode($response->getBody(), true);
            $price = $data["last"]["price"] ?? null;

            // Cache for 10 seconds
            if ($price) {
                Cache::put($cacheKey, $price, 10);
            }

            return $price;
        } catch (\Exception $e) {
            Log::error("Alpaca API error getting price: " . $e->getMessage());
            return null;
        }
    }

    // Place an order
    public function placeOrder($symbol, $qty, $side, $type = "market", $time_in_force = "day", $limitPrice = null, $stopPrice = null) {
        try {
            $data = [
                "symbol" => $symbol,
                "qty" => $qty,
                "side" => $side,
                "type" => $type,
                "time_in_force" => $time_in_force
            ];

            if ($type === "limit" && $limitPrice) {
                $data["limit_price"] = $limitPrice;
            }

            if ($type === "stop" && $stopPrice) {
                $data["stop_price"] = $stopPrice;
            }

            $response = $this->client->post("/v2/orders", [
                "json" => $data
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error("Alpaca API error placing order: " . $e->getMessage());
            return [
                "success" => false, 
                "message" => $e->getMessage()
            ];
        }
    }

    // Get open positions
    public function getPositions() {
        try {
            $response = $this->client->get("/v2/positions");
            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error("Alpaca API error getting positions: " . $e->getMessage());
            return [];
        }
    }

    // Get historical data for a symbol
    public function getHistoricalData($symbol, $timeframe = "1Day", $limit = 100) {
        try {
            $response = $this->client->get("/v2/stocks/{$symbol}/bars", [
                "query" => [
                    "timeframe" => $timeframe,
                    "limit" => $limit
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            // Transform to common format
            $result = [];
            foreach ($data["bars"] ?? [] as $bar) {
                $result[] = [
                    "open" => $bar["o"],
                    "high" => $bar["h"],
                    "low" => $bar["l"],
                    "close" => $bar["c"],
                    "volume" => $bar["v"],
                    "timestamp" => $bar["t"]
                ];
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("Alpaca API error getting historical data: " . $e->getMessage());
            return [];
        }
    }

    // Get available forex pairs
    public function getForexPairs() {
        $majorPairs = [
            "EUR/USD", "USD/JPY", "GBP/USD", "USD/CHF", 
            "AUD/USD", "USD/CAD", "NZD/USD"
        ];

        return $majorPairs;
    }
}
