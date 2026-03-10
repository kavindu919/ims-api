<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ActivityLogController extends Controller
{
    public function getAllActivityLogs(Request $request)
    {
        try {
            $query = ActivityLog::with('user:id,name,email')
                ->orderBy('created_at', 'desc');

            if ($request->action) {
                $request->validate([
                    'action' => 'string'
                ]);
                $query->where('action', $request->action);
            }

            if ($request->user_id) {
                $request->validate([
                    'user_id' => 'integer|exists:users,id'
                ]);
                $query->where('user_id', $request->user_id);
            }

            if ($request->from) {
                $request->validate([
                    'from' => 'date'
                ]);
                $query->whereDate('created_at', '>=', $request->from);
            }

            if ($request->to) {
                $request->validate([
                    'to' => 'date'
                ]);
                $query->whereDate('created_at', '<=', $request->to);
            }

            $logs = $query->paginate(50);

            return response()->json([
                'success' => true,
                'message' => 'Activity logs retrieved successfully.',
                'logs' => $logs,
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Fetching activity logs failed: ' . $th->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch activity logs, please try again later.'
            ], 500);
        }
    }
}
