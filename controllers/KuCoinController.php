<?php
// KuCoinController.php
// Controller for handling KuCoin (crypto) API actions

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\KuCoinConnector;

class KuCoinController extends Controller
{
    protected $kucoin;

    public function __construct(KuCoinConnector $kucoin)
    {
        $this->kucoin = $kucoin;
    }

    // Get account balances
    public function balance(Request $request)
    {
        $result = $this->kucoin->getBalances();
        return response()->json(json_decode($result->getBody(), true));
    }

    // Place order (buy/sell/stop-limit)
    public function placeOrder(Request $request)
    {
        $symbol = $request->input('symbol');
        $side = $request->input('side'); // buy or sell
        $type = $request->input('type', 'market'); // market, limit, stop_limit
        $size = $request->input('size');
        $price = $request->input('price', null);
        $stop = $request->input('stop', null);
        $stopPrice = $request->input('stopPrice', null);
        $result = $this->kucoin->placeOrder($symbol, $side, $type, $size, $price, $stop, $stopPrice);
        return response()->json(json_decode($result->getBody(), true));
    }

    // Get ticker (market data)
    public function ticker(Request $request)
    {
        $symbol = $request->input('symbol');
        $result = $this->kucoin->getTicker($symbol);
        return response()->json(json_decode($result->getBody(), true));
    }

    // Get order book (market depth)
    public function orderBook(Request $request)
    {
        $symbol = $request->input('symbol');
        $result = $this->kucoin->getOrderBook($symbol);
        return response()->json(json_decode($result->getBody(), true));
    }

    // Get available symbols (markets)
    public function symbols()
    {
        $result = $this->kucoin->getSymbols();
        return response()->json(json_decode($result->getBody(), true));
    }

    // Get recent trades for a symbol
    public function trades(Request $request)
    {
        $symbol = $request->input('symbol');
        $result = $this->kucoin->getTrades($symbol);
        return response()->json(json_decode($result->getBody(), true));
    }

    // API endpoint for single symbol details
    public function symbol(Request $request)
    {
        $symbol = $request->input('symbol');
        $result = $this->kucoin->getSymbol($symbol);
        return response()->json(json_decode($result->getBody(), true));
    }

    // API endpoint to clear symbol cache (admin or on-demand)
    public function clearSymbolCache(Request $request)
    {
        $symbol = $request->input('symbol');
        $result = $this->kucoin->clearSymbolCache($symbol);
        return $result;
    }
}
