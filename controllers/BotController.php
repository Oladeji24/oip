<?php
// BotController.php
// Controller for handling bot trading actions (start, stop, switch, status)

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Bot\BotLogic;

class BotController extends Controller
{
    protected $bot;

    public function __construct(BotLogic $bot)
    {
        $this->bot = $bot;
    }

    public function start(Request $request)
    {
        $validated = $request->validate([
            'market' => 'required|string|in:crypto,forex',
            'symbol' => 'required|string',
        ]);
        $userId = $request->user()->id;
        $result = $this->bot->startBot($userId, $validated['market'], $validated['symbol']);
        return response()->json($result);
    }

    public function stop(Request $request)
    {
        $userId = $request->user()->id ?? 1;
        $result = $this->bot->stopBot($userId);
        return response()->json($result);
    }

    public function switch(Request $request)
    {
        $userId = $request->user()->id ?? 1;
        $newMarket = $request->input('market');
        $result = $this->bot->switchMarket($userId, $newMarket);
        return response()->json($result);
    }

    // Get bot analytics and performance stats
    public function analytics(Request $request)
    {
        $userId = $request->user()->id ?? 1;
        $result = $this->bot->getBotAnalytics($userId);
        return response()->json($result);
    }

    // Allow user to update bot parameters (EMA/RSI periods, risk)
    public function updateParams(Request $request)
    {
        $validated = $request->validate([
            'strategy' => 'required|string',
            'emaFast' => 'nullable|integer|min:2|max:50',
            'emaSlow' => 'nullable|integer|min:2|max:100',
            'rsiPeriod' => 'nullable|integer|min:2|max:50',
            'macdFast' => 'nullable|integer|min:2|max:50',
            'macdSlow' => 'nullable|integer|min:2|max:50',
            'macdSignal' => 'nullable|integer|min:1|max:50',
            'riskLevel' => 'nullable|integer|min:1|max:5',
            'tripleFast' => 'nullable|integer|min:2|max:49',
            'tripleMid' => 'nullable|integer|min:3|max:99',
            'tripleSlow' => 'nullable|integer|min:16|max:200',
        ]);
        $userId = $request->user()->id;
        $result = $this->bot->updateBotParams($userId, $validated);
        return response()->json($result);
    }

    // Get current user bot parameters
    public function getParams(Request $request)
    {
        $userId = $request->user()->id ?? 1;
        $params = $this->bot->getUserParams($userId);
        return response()->json($params);
    }

    // Get advanced bot analytics
    public function advancedAnalytics(Request $request)
    {
        $userId = $request->user()->id ?? 1;
        $result = $this->bot->getBotAdvancedAnalytics($userId);
        return response()->json($result);
    }
}
