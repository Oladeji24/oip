<?php
// KrakenController.php
// Controller for handling Kraken (crypto) API actions

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\KrakenConnector;

class KrakenController extends Controller
{
    protected $kraken;

    public function __construct(KrakenConnector $kraken)
    {
        $this->kraken = $kraken;
    }

    // Example: Get account balance
    public function balance(Request $request)
    {
        $result = $this->kraken->getBalance();
        return response()->json($result);
    }

    // Example: Place order
    public function placeOrder(Request $request)
    {
        $pair = $request->input('pair');
        $type = $request->input('type');
        $volume = $request->input('volume');
        $result = $this->kraken->placeOrder($pair, $type, $volume);
        return response()->json($result);
    }
}
