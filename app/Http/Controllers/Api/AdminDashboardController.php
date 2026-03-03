<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminDashboardController extends Controller
{
    /**
     * System overview — privacy-safe stats (no financial data).
     */
    public function overview(Request $request)
    {
        $now = Carbon::now();

        // User counts
        $totalUsers   = User::count();
        $activeUsers  = User::where('is_banned', false)->count();
        $bannedUsers  = User::where('is_banned', true)->count();
        $adminUsers   = User::role('admin')->count();

        // New registrations
        $newToday     = User::whereDate('created_at', $now->toDateString())->count();
        $newThisWeek  = User::where('created_at', '>=', $now->startOfWeek())->count();
        $newThisMonth = User::where('created_at', '>=', $now->copy()->startOfMonth())->count();

        // Active users (based on activity logs)
        $active24h = ActivityLog::where('created_at', '>=', $now->copy()->subHours(24))
            ->distinct('user_id')->count('user_id');
        $active7d  = ActivityLog::where('created_at', '>=', $now->copy()->subDays(7))
            ->distinct('user_id')->count('user_id');
        $active30d = ActivityLog::where('created_at', '>=', $now->copy()->subDays(30))
            ->distinct('user_id')->count('user_id');

        // Registration trend (last 30 days)
        $registrationTrend = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', $now->copy()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top actions (last 30 days)
        $topActions = ActivityLog::selectRaw('action, COUNT(*) as count')
            ->where('created_at', '>=', $now->copy()->subDays(30))
            ->groupBy('action')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Module usage (last 30 days)
        $moduleUsage = ActivityLog::selectRaw('module, COUNT(*) as count')
            ->whereNotNull('module')
            ->where('created_at', '>=', $now->copy()->subDays(30))
            ->groupBy('module')
            ->orderByDesc('count')
            ->get();

        // Recent activity (last 20 entries)
        $recentActivity = ActivityLog::with('user:id,name,email')
            ->latest()
            ->take(20)
            ->get(['id', 'user_id', 'action', 'module', 'description', 'created_at']);

        // Roles distribution
        $rolesDistribution = [];
        foreach (['admin', 'user', 'guest'] as $role) {
            $rolesDistribution[] = [
                'role'  => $role,
                'count' => User::role($role)->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'users' => [
                    'total'    => $totalUsers,
                    'active'   => $activeUsers,
                    'banned'   => $bannedUsers,
                    'admins'   => $adminUsers,
                ],
                'growth' => [
                    'today'      => $newToday,
                    'this_week'  => $newThisWeek,
                    'this_month' => $newThisMonth,
                ],
                'engagement' => [
                    'active_24h' => $active24h,
                    'active_7d'  => $active7d,
                    'active_30d' => $active30d,
                ],
                'registration_trend'  => $registrationTrend,
                'top_actions'         => $topActions,
                'module_usage'        => $moduleUsage,
                'recent_activity'     => $recentActivity,
                'roles_distribution'  => $rolesDistribution,
            ],
        ]);
    }
}
