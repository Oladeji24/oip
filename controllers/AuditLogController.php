<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AuditLog;

class AuditLogController extends Controller
{
    // List audit logs (admin/superadmin only)
    public function index(Request $request)
    {
        if (!$request->user() || !in_array($request->user()->role, ['admin', 'superadmin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $logs = AuditLog::with('user:id,name,email,role')->latest()->limit(100)->get();
        return response()->json(['success' => true, 'logs' => $logs]);
    }

    // Log an admin action
    public function store(Request $request)
    {
        if (!$request->user() || !in_array($request->user()->role, ['admin', 'superadmin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        $data = $request->validate([
            'action' => 'required|string',
            'details' => 'nullable|string',
        ]);
        $log = AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $data['action'],
            'details' => $data['details'] ?? null,
        ]);
        return response()->json(['success' => true, 'log' => $log]);
    }
}
