<?php
// DerivController.php
// Controller for handling Deriv (forex) API actions

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DerivConnector;

class DerivController extends Controller
{
    protected $deriv;

    public function __construct(DerivConnector $deriv)
    {
        $this->deriv = $deriv;
    }

    // Get account info
    public function account(Request $request)
    {
        $result = $this->deriv->getAccountInfo();
        return response()->json(json_decode($result->getBody(), true));
    }

    // Place order (buy/sell)
    public function placeOrder(Request $request)
    {
        $symbol = $request->input('symbol');
        $side = $request->input('side'); // buy or sell
        $amount = $request->input('amount');
        $result = $this->deriv->placeOrder($symbol, $side, $amount);
        return response()->json(json_decode($result->getBody(), true));
    }

    // Get available symbols (markets)
    public function symbols()
    {
        $result = $this->deriv->getSymbols();
        return response()->json(json_decode($result->getBody(), true));
    }

    // Get recent trades for a symbol (mocked)
    public function trades(Request $request)
    {
        $symbol = $request->input('symbol');
        $result = $this->deriv->getTrades($symbol);
        return response()->json($result);
    }

    // API endpoint for single symbol details
    public function symbol(Request $request)
    {
        $symbol = $request->input('symbol');
        $result = $this->deriv->getSymbol($symbol);
        return $result;
    }

    // API endpoint to clear symbol cache (admin or on-demand)
    public function clearSymbolCache(Request $request)
    {
        $symbol = $request->input('symbol');
        $result = $this->deriv->clearSymbolCache($symbol);
        return $result;
    }
}
