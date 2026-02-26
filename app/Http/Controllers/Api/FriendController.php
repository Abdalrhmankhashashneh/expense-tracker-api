<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FriendRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FriendController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $incoming = FriendRequest::with('sender:id,name,email')
            ->where('receiver_id', $userId)
            ->where('status', FriendRequest::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'status' => $item->status,
                'created_at' => $item->created_at->toIso8601String(),
                'user' => [
                    'id' => $item->sender?->id,
                    'name' => $item->sender?->name,
                    'email' => $item->sender?->email,
                ],
            ]);

        $sent = FriendRequest::with('receiver:id,name,email')
            ->where('sender_id', $userId)
            ->where('status', FriendRequest::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'status' => $item->status,
                'created_at' => $item->created_at->toIso8601String(),
                'user' => [
                    'id' => $item->receiver?->id,
                    'name' => $item->receiver?->name,
                    'email' => $item->receiver?->email,
                ],
            ]);

        $accepted = FriendRequest::with(['sender:id,name,email', 'receiver:id,name,email'])
            ->where(function ($query) use ($userId) {
                $query->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->where('status', FriendRequest::STATUS_ACCEPTED)
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($item) use ($userId) {
                $friend = $item->sender_id === $userId ? $item->receiver : $item->sender;

                return [
                    'id' => $item->id,
                    'friends_since' => ($item->responded_at ?? $item->updated_at)?->toIso8601String(),
                    'user' => [
                        'id' => $friend?->id,
                        'name' => $friend?->name,
                        'email' => $friend?->email,
                    ],
                ];
            })
            ->values();

        return response()->json([
            'message' => 'Friends data retrieved successfully.',
            'data' => [
                'incoming_requests' => $incoming,
                'sent_requests' => $sent,
                'friends' => $accepted,
            ],
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $currentUserId = $request->user()->id;
        $term = trim($validated['q']);

        $users = User::query()
            ->where('id', '!=', $currentUserId)
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'email']);

        return response()->json([
            'message' => 'Users retrieved successfully.',
            'data' => $users,
        ]);
    }

    public function sendRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receiver_email' => 'required|email',
        ]);

        $sender = $request->user();
        $receiver = User::where('email', $validated['receiver_email'])->first();

        if (!$receiver) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        if ($sender->id === $receiver->id) {
            return response()->json([
                'message' => 'You cannot send a friend request to yourself.',
            ], 422);
        }

        $existing = FriendRequest::where(function ($query) use ($sender, $receiver) {
                $query->where('sender_id', $sender->id)
                    ->where('receiver_id', $receiver->id);
            })
            ->orWhere(function ($query) use ($sender, $receiver) {
                $query->where('sender_id', $receiver->id)
                    ->where('receiver_id', $sender->id);
            })
            ->whereIn('status', [FriendRequest::STATUS_PENDING, FriendRequest::STATUS_ACCEPTED])
            ->first();

        if ($existing) {
            if ($existing->status === FriendRequest::STATUS_ACCEPTED) {
                return response()->json([
                    'message' => 'You are already friends.',
                ], 409);
            }

            if ($existing->sender_id === $sender->id) {
                return response()->json([
                    'message' => 'Friend request already sent.',
                ], 409);
            }

            return response()->json([
                'message' => 'This user already sent you a friend request.',
            ], 409);
        }

        $friendRequest = FriendRequest::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'status' => FriendRequest::STATUS_PENDING,
        ]);

        return response()->json([
            'message' => 'Friend request sent successfully.',
            'data' => [
                'id' => $friendRequest->id,
                'status' => $friendRequest->status,
                'receiver' => [
                    'id' => $receiver->id,
                    'name' => $receiver->name,
                    'email' => $receiver->email,
                ],
            ],
        ], 201);
    }

    public function accept(Request $request, int $id): JsonResponse
    {
        $friendRequest = FriendRequest::where('id', $id)
            ->where('receiver_id', $request->user()->id)
            ->where('status', FriendRequest::STATUS_PENDING)
            ->firstOrFail();

        $friendRequest->update([
            'status' => FriendRequest::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);

        return response()->json([
            'message' => 'Friend request accepted.',
        ]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $friendRequest = FriendRequest::where('id', $id)
            ->where('receiver_id', $request->user()->id)
            ->where('status', FriendRequest::STATUS_PENDING)
            ->firstOrFail();

        $friendRequest->update([
            'status' => FriendRequest::STATUS_REJECTED,
            'responded_at' => now(),
        ]);

        return response()->json([
            'message' => 'Friend request rejected.',
        ]);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $friendRequest = FriendRequest::where('id', $id)
            ->where('sender_id', $request->user()->id)
            ->where('status', FriendRequest::STATUS_PENDING)
            ->firstOrFail();

        $friendRequest->update([
            'status' => FriendRequest::STATUS_CANCELLED,
            'responded_at' => now(),
        ]);

        return response()->json([
            'message' => 'Friend request cancelled.',
        ]);
    }

    public function remove(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;

        $friendRequest = FriendRequest::where('id', $id)
            ->where(function ($query) use ($userId) {
                $query->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->where('status', FriendRequest::STATUS_ACCEPTED)
            ->firstOrFail();

        $friendRequest->update([
            'status' => FriendRequest::STATUS_REMOVED,
            'responded_at' => now(),
        ]);

        return response()->json([
            'message' => 'Friend removed successfully.',
        ]);
    }
}
