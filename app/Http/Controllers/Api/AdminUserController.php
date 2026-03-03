<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    /**
     * List all users with pagination, search, and filters.
     */
    public function index(Request $request)
    {
        $query = User::with('roles');

        // Search by name or email
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $status = $request->input('status');
            if ($status === 'banned') {
                $query->where('is_banned', true);
            } elseif ($status === 'active') {
                $query->where('is_banned', false);
            }
        }

        // Filter by role
        if ($role = $request->input('role')) {
            $query->role($role);
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $allowedSorts = ['name', 'email', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $users = $query->paginate($request->input('per_page', 15));

        // Transform users — exclude sensitive financial data
        $users->getCollection()->transform(function ($user) {
            return [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'roles'      => $user->getRoleNames(),
                'is_banned'  => (bool) $user->is_banned,
                'ban_reason' => $user->ban_reason,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $users,
        ]);
    }

    /**
     * Get a single user's detail (privacy-safe — no financial data).
     */
    public function show(User $user)
    {
        // Activity counts — behavior only, no amounts
        $activityCounts = [
            'expenses'   => $user->expenses()->count(),
            'debts'      => $user->debts()->count(),
            'lendings'   => $user->lendings()->count(),
            'targets'    => $user->targets()->count(),
            'categories' => $user->categories()->count(),
            'exports'    => $user->exportHistory()->count(),
        ];

        // Recent activity logs
        $recentLogs = $user->activityLogs()
            ->latest()
            ->take(20)
            ->get(['action', 'module', 'description', 'ip_address', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => [
                'id'              => $user->id,
                'name'            => $user->name,
                'email'           => $user->email,
                'roles'           => $user->getRoleNames(),
                'is_banned'       => (bool) $user->is_banned,
                'ban_reason'      => $user->ban_reason,
                'banned_at'       => $user->banned_at,
                'created_at'      => $user->created_at,
                'updated_at'      => $user->updated_at,
                'activity_counts' => $activityCounts,
                'recent_activity' => $recentLogs,
            ],
        ]);
    }

    /**
     * Create a new user (by admin).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role'     => ['required', 'string', Rule::in(['admin', 'user', 'guest'])],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        ActivityLogService::log(
            $request->user()->id,
            'admin_create_user',
            'admin',
            "Created user: {$user->email}",
            $request
        );

        return response()->json([
            'success' => true,
            'message' => __('messages.admin.user_created'),
            'data'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ],
        ], 201);
    }

    /**
     * Update user details (name, email, role).
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'  => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'role'  => ['sometimes', 'string', Rule::in(['admin', 'user', 'guest'])],
        ]);

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }
        $user->save();

        if (isset($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        ActivityLogService::log(
            $request->user()->id,
            'admin_update_user',
            'admin',
            "Updated user: {$user->email}",
            $request
        );

        return response()->json([
            'success' => true,
            'message' => __('messages.admin.user_updated'),
            'data'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ],
        ]);
    }

    /**
     * Reset user password (admin sets a new one).
     */
    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Revoke all existing tokens
        $user->tokens()->delete();

        ActivityLogService::log(
            $request->user()->id,
            'admin_reset_password',
            'admin',
            "Reset password for: {$user->email}",
            $request
        );

        return response()->json([
            'success' => true,
            'message' => __('messages.admin.password_reset'),
        ]);
    }

    /**
     * Ban a user.
     */
    public function ban(Request $request, User $user)
    {
        // Prevent self-ban
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => __('messages.admin.cannot_ban_self'),
            ], 422);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $user->update([
            'is_banned'  => true,
            'banned_at'  => now(),
            'ban_reason' => $validated['reason'] ?? null,
        ]);

        // Revoke all tokens so they're logged out immediately
        $user->tokens()->delete();

        ActivityLogService::log(
            $request->user()->id,
            'admin_ban_user',
            'admin',
            "Banned user: {$user->email}" . ($validated['reason'] ? " — {$validated['reason']}" : ''),
            $request
        );

        return response()->json([
            'success' => true,
            'message' => __('messages.admin.user_banned'),
        ]);
    }

    /**
     * Unban a user.
     */
    public function unban(Request $request, User $user)
    {
        $user->update([
            'is_banned'  => false,
            'banned_at'  => null,
            'ban_reason' => null,
        ]);

        ActivityLogService::log(
            $request->user()->id,
            'admin_unban_user',
            'admin',
            "Unbanned user: {$user->email}",
            $request
        );

        return response()->json([
            'success' => true,
            'message' => __('messages.admin.user_unbanned'),
        ]);
    }

    /**
     * Delete a user.
     */
    public function destroy(Request $request, User $user)
    {
        // Prevent self-delete
        if ($user->id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => __('messages.admin.cannot_delete_self'),
            ], 422);
        }

        ActivityLogService::log(
            $request->user()->id,
            'admin_delete_user',
            'admin',
            "Deleted user: {$user->email}",
            $request
        );

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => __('messages.admin.user_deleted'),
        ]);
    }
}
