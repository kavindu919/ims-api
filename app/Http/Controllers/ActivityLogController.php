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
            $request->validate([
                'action'    => 'nullable|string|in:item_created,item_updated,status_changed,quantity_changed,borrowed,returned,user_created,user_updated',
                'user_id'   => 'nullable|integer|exists:users,id',
                'from'      => 'nullable|date',
                'to'        => 'nullable|date|after_or_equal:from',
                'sortOrder' => 'nullable|in:asc,desc',
                'page'      => 'nullable|integer|min:1',
                'limit'     => 'nullable|integer|min:1|max:100',
            ]);

            $sortOrder = $request->sortOrder ?? 'desc';
            $page      = $request->page ?? 1;
            $limit     = $request->limit ?? 50;

            $logs = ActivityLog::with('user:id,name,email')
                ->select('id', 'user_id', 'action', 'subject_type', 'subject_id', 'old_value', 'new_value', 'created_at')
                ->when($request->action,  fn($q) => $q->where('action', $request->action))
                ->when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
                ->when($request->from,    fn($q) => $q->whereDate('created_at', '>=', $request->from))
                ->when($request->to,      fn($q) => $q->whereDate('created_at', '<=', $request->to))
                ->orderBy('created_at', $sortOrder)
                ->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'message' => 'Activity logs retrieved successfully.',
                'logs'    => $logs->items(),
                'meta'    => [
                    'total' => $logs->total(),
                    'page'  => $logs->currentPage(),
                    'limit' => $logs->perPage(),
                ],
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
