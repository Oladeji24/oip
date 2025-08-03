<?php
// TransactionController.php
// Controller for logging and retrieving user actions and transactions

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TransactionLogger;

class TransactionController extends Controller
{
    protected $logger;

    public function __construct(TransactionLogger $logger)
    {
        $this->logger = $logger;
    }

    // Log a user action
    public function logAction(Request $request)
    {
        $userId = $request->user()->id ?? 1;
        $action = $request->input('action');
        $details = $request->input('details', []);
        $this->logger->logAction($userId, $action, $details);
        return response()->json(['success' => true]);
    }

    // Log a transaction
    public function logTransaction(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'amount' => 'required|numeric',
            'currency' => 'required|string|in:NGN,USD',
            'status' => 'required|string',
            'details' => 'nullable|array',
        ]);
        $userId = $request->user()->id;
        $this->logger->logTransaction($userId, $validated['type'], $validated['amount'], $validated['currency'], $validated['status'], $validated['details'] ?? []);
        return response()->json(['success' => true]);
    }

    // Get user trade analytics (P&L, volume, win/loss, time series, by market, filter by market)
    public function analytics(Request $request)
    {
        $userId = $request->user()->id ?? 1;
        $marketFilter = $request->query('market');
        $query = \DB::table('transactions')
            ->where('user_id', $userId)
            ->where('type', 'trade');
        if ($marketFilter) {
            $query->where('market', $marketFilter);
        }
        $trades = $query->orderBy('created_at')->get();
        $pnl = 0;
        $volume = 0;
        $win = 0;
        $loss = 0;
        $byMarket = [];
        $pnlSeries = [];
        $volumeSeries = [];
        $winLossSeries = [];
        foreach ($trades as $trade) {
            $pnl += $trade->profit ?? 0;
            $volume += $trade->amount;
            if (($trade->profit ?? 0) > 0) $win++;
            if (($trade->profit ?? 0) < 0) $loss++;
            $market = $trade->market ?? 'unknown';
            if (!isset($byMarket[$market])) $byMarket[$market] = ['pnl' => 0, 'volume' => 0, 'count' => 0];
            $byMarket[$market]['pnl'] += $trade->profit ?? 0;
            $byMarket[$market]['volume'] += $trade->amount;
            $byMarket[$market]['count']++;
            $pnlSeries[] = [
                'time' => $trade->created_at,
                'pnl' => $trade->profit ?? 0
            ];
            $volumeSeries[] = [
                'time' => $trade->created_at,
                'volume' => $trade->amount
            ];
            $winLossSeries[] = [
                'time' => $trade->created_at,
                'result' => ($trade->profit ?? 0) > 0 ? 'win' : (($trade->profit ?? 0) < 0 ? 'loss' : 'even')
            ];
        }
        return response()->json([
            'pnl' => $pnl,
            'volume' => $volume,
            'win' => $win,
            'loss' => $loss,
            'total' => $trades->count(),
            'byMarket' => $byMarket,
            'pnlSeries' => $pnlSeries,
            'volumeSeries' => $volumeSeries,
            'winLossSeries' => $winLossSeries,
            'trades' => $trades,
        ]);
    }
}
