<?php
// AlpacaController.php
// Controller for handling Alpaca (forex) API actions

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AlpacaConnector;

class AlpacaController extends Controller
{
    protected $alpaca;

    public function __construct(AlpacaConnector $alpaca)
    {
        $this->alpaca = $alpaca;
    }

    // Example: Get account info
    public function account(Request $request)
    {
        $result = $this->alpaca->getAccount();
        return response()->json($result);
    }

    // Example: Place order
    public function placeOrder(Request $request)
    {
        $symbol = $request->input('symbol');
        $qty = $request->input('qty');
        $side = $request->input('side');
        $type = $request->input('type');
        $tif = $request->input('time_in_force');
        $result = $this->alpaca->placeOrder($symbol, $qty, $side, $type, $tif);
        return response()->json($result);
    }
}
