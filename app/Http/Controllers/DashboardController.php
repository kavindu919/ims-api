<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BorrowRecord;
use App\Models\Cupboard;
use App\Models\InventoryItem;
use App\Models\StoragePlace;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function getStats()
    {
        try {
            $totalItems       = InventoryItem::count();
            $totalInStore     = InventoryItem::where('status', 'in_store')->count();
            $totalBorrowed    = InventoryItem::where('status', 'borrowed')->count();
            $totalDamaged     = InventoryItem::where('status', 'damaged')->count();
            $totalMissing     = InventoryItem::where('status', 'missing')->count();
            $totalCupboards   = Cupboard::count();
            $totalPlaces      = StoragePlace::count();
            $totalUsers       = User::count();
            $activeUsers      = User::where('is_active', true)->count();
            $activeBorrows    = BorrowRecord::where('status', 'borrowed')->count();
            $overdueBorrows   = BorrowRecord::where('status', 'borrowed')
                ->whereDate('expected_return_date', '<', now())
                ->count();

            $recentLogs = ActivityLog::with('user:id,name')
                ->select('id', 'user_id', 'action', 'subject_type', 'subject_id', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Dashboard stats retrieved successfully.',
                'stats'   => [
                    'inventory' => [
                        'total'     => $totalItems,
                        'in_store'  => $totalInStore,
                        'borrowed'  => $totalBorrowed,
                        'damaged'   => $totalDamaged,
                        'missing'   => $totalMissing,
                    ],
                    'storage' => [
                        'cupboards' => $totalCupboards,
                        'places'    => $totalPlaces,
                    ],
                    'users' => [
                        'total'  => $totalUsers,
                        'active' => $activeUsers,
                    ],
                    'borrows' => [
                        'active'   => $activeBorrows,
                        'overdue'  => $overdueBorrows,
                    ],
                ],
                'recent_logs' => $recentLogs,
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Fetching dashboard stats failed: ' . $th->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard stats, please try again later.'
            ], 500);
        }
    }
}
