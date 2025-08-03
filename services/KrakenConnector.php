<?php
// KrakenConnector.php
// Service for interacting with the Kraken API (Crypto Spot Trading)
// Now fully implemented with proper API authentication and methods

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class KrakenConnector {
    protected $apiKey;
    protected $apiSecret;
    protected $client;
    protected $baseUrl = "https://api.kraken.com";

    public function __construct() {
        $this->apiKey = env("KRAKEN_API_KEY");
        $this->apiSecret = env("KRAKEN_API_SECRET");
        $this->client = new Client([
            "base_uri" => $this->baseUrl,
            "timeout" => 30,
            "headers" => [
                "User-Agent" => "OIPTradingBot/1.0"
            ]
        ]);
    }

    /**
     * Get account balance
     *
     * @return array Account balance or error message
     */
    public function getBalance() {
        return $this->privateRequest("Balance");
    }

    /**
     * Get current price for a symbol
     * 
     * @param string $symbol Symbol e.g. "BTCUSD"
     * @return float|null Current price or null if error
     */
    public function getCurrentPrice($symbol) {
        try {
            // Kraken uses XBT instead of BTC, so we need to convert
            $symbol = str_replace("BTC", "XBT", $symbol);

            // Use the ticker endpoint to get current price
            $cacheKey = "kraken_price_" . $symbol;

            // Check cache first (10 seconds)
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            $response = $this->client->get("/0/public/Ticker", [
                "query" => ["pair" => $symbol]
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data["error"]) && !empty($data["error"])) {
                Log::error("Kraken API error: " . implode(", ", $data["error"]));
                return null;
            }

            // Kraken returns data with the pair as the key
            $result = $data["result"];
            $pair = array_keys($result)[0];

            // The close price is the current price
            $price = $result[$pair]["c"][0] ?? null;

            // Cache for 10 seconds
            if ($price) {
                Cache::put($cacheKey, (float)$price, 10);
                return (float)$price;
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Kraken API error getting price: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Place order on Kraken
     *
     * @param string $pair Trading pair e.g. "XBTUSD"
     * @param string $type buy/sell
     * @param float $volume Amount to buy/sell
     * @param string $orderType Market/Limit
     * @param float|null $price Price for limit orders
     * @return array Order result or error message
     */
    public function placeOrder($pair, $type, $volume, $orderType = "market", $price = null) {
        $params = [
            "pair" => $pair,
            "type" => $type,
            "volume" => $volume,
            "ordertype" => $orderType
        ];

        if ($orderType === "limit" && $price !== null) {
            $params["price"] = $price;
        }

        return $this->privateRequest("AddOrder", $params);
    }

    /**
     * Get historical data for a symbol
     * 
     * @param string $symbol Symbol e.g. "BTCUSD"
     * @param int $interval Time interval in minutes
     * @param int $since Timestamp to get data from
     * @return array Array of historical data points
     */
    public function getHistoricalData($symbol, $interval = 1440, $since = null) {
        try {
            // Kraken uses XBT instead of BTC, so we need to convert
            $symbol = str_replace("BTC", "XBT", $symbol);

            $params = [
                "pair" => $symbol,
                "interval" => $interval
            ];

            if ($since !== null) {
                $params["since"] = $since;
            }

            $response = $this->client->get("/0/public/OHLC", [
                "query" => $params
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data["error"]) && !empty($data["error"])) {
                Log::error("Kraken API error: " . implode(", ", $data["error"]));
                return [];
            }

            // Kraken returns data with the pair as the key
            $result = $data["result"];
            $pair = array_keys($result)[0];

            if (!isset($result[$pair]) || !is_array($result[$pair])) {
                return [];
            }

            // Format the data
            $historicalData = [];
            foreach ($result[$pair] as $item) {
                $historicalData[] = [
                    "timestamp" => $item[0],
                    "open" => (float)$item[1],
                    "high" => (float)$item[2],
                    "low" => (float)$item[3],
                    "close" => (float)$item[4],
                    "vwap" => (float)$item[5],
                    "volume" => (float)$item[6],
                    "count" => (int)$item[7]
                ];
            }

            return $historicalData;
        } catch (\Exception $e) {
            Log::error("Kraken API error getting historical data: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get open positions
     * 
     * @return array Open positions
     */
    public function getOpenPositions() {
        return $this->privateRequest("OpenPositions");
    }

    /**
     * Make authenticated private request to Kraken API
     *
     * @param string $method API method name
     * @param array $params Additional parameters
     * @return array API response
     */
    protected function privateRequest($method, $params = []) {
        try {
            $path = "/0/private/" . $method;

            // Add nonce for security
            $params["nonce"] = round(microtime(true) * 1000);

            $signature = $this->getSignature($path, $params);

            $response = $this->client->post($path, [
                "headers" => [
                    "API-Key" => $this->apiKey,
                    "API-Sign" => $signature
                ],
                "form_params" => $params
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data["error"]) && !empty($data["error"])) {
                Log::error("Kraken API error: " . implode(", ", $data["error"]));
                return [
                    "success" => false,
                    "message" => implode(", ", $data["error"])
                ];
            }

            return [
                "success" => true,
                "data" => $data["result"]
            ];
        } catch (\Exception $e) {
            Log::error("Kraken API error: " . $e->getMessage());
            return [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
    }

    /**
     * Generate API request signature
     *
     * @param string $path API endpoint path
     * @param array $params Request parameters
     * @return string Request signature
     */
    protected function getSignature($path, $params) {
        $postData = http_build_query($params);
        $nonce = $params["nonce"];

        // Calculate signature
        $signature = hash_hmac(
            "sha512",
            $path . hash("sha256", $nonce . $postData, true),
            base64_decode($this->apiSecret),
            true
        );

        return base64_encode($signature);
    }
}
