<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShareToken;
use App\Models\Debt;
use App\Models\Lending;
use App\Mail\ShareNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class ShareController extends Controller
{
    /**
     * Create a shareable link for a debt or lending.
     */
    public function createShare(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shareable_type' => ['required', Rule::in(['debt', 'lending'])],
            'shareable_id' => 'required|integer',
            'share_type' => ['required', Rule::in(ShareToken::SHARE_TYPES)],
            'recipient_email' => 'required_if:share_type,email|nullable|email',
            'expires_in_days' => 'nullable|integer|min:1|max:365',
        ]);

        // Map type to model class
        $modelClass = $validated['shareable_type'] === 'debt' ? Debt::class : Lending::class;

        // Find the resource and verify ownership
        $resource = $modelClass::findOrFail($validated['shareable_id']);

        if ($resource->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You do not have permission to share this resource.',
            ], 403);
        }

        // Calculate expiration
        $expiresAt = null;
        if (!empty($validated['expires_in_days'])) {
            $expiresAt = now()->addDays($validated['expires_in_days']);
        }

        // Create share token
        $shareToken = ShareToken::create([
            'user_id' => $request->user()->id,
            'shareable_type' => $modelClass,
            'shareable_id' => $resource->id,
            'share_type' => $validated['share_type'],
            'recipient_email' => $validated['recipient_email'] ?? null,
            'expires_at' => $expiresAt,
        ]);

        // Send email if share type is email
        if ($validated['share_type'] === ShareToken::TYPE_EMAIL && !empty($validated['recipient_email'])) {
            Mail::to($validated['recipient_email'])->send(
                new ShareNotification($shareToken, $request->user())
            );
            $shareToken->update(['email_sent_at' => now()]);
        }

        // Generate the share URL
        $shareUrl = $this->generateShareUrl($shareToken);

        return response()->json([
            'message' => 'Share link created successfully.',
            'data' => [
                'id' => $shareToken->id,
                'token' => $shareToken->token,
                'share_url' => $shareUrl,
                'share_type' => $shareToken->share_type,
                'expires_at' => $shareToken->expires_at?->toIso8601String(),
                'recipient_email' => $shareToken->recipient_email,
                'email_sent' => $shareToken->email_sent_at !== null,
            ],
        ], 201);
    }

    /**
     * View a shared resource (public endpoint).
     */
    public function viewShare(string $token): JsonResponse
    {
        $shareToken = ShareToken::where('token', $token)->first();

        if (!$shareToken) {
            return response()->json([
                'message' => 'Share link not found.',
            ], 404);
        }

        if (!$shareToken->isValid()) {
            return response()->json([
                'message' => $shareToken->isExpired()
                    ? 'This share link has expired.'
                    : 'This share link is no longer active.',
            ], 410);
        }

        // Record the view
        $shareToken->recordView();

        // Load the shareable resource with relevant data
        $resource = $shareToken->shareable;

        if (!$resource) {
            return response()->json([
                'message' => 'The shared resource no longer exists.',
            ], 404);
        }

        // Prepare response based on resource type
        $data = $this->formatSharedResource($resource, $shareToken);

        return response()->json([
            'message' => 'Shared resource retrieved successfully.',
            'data' => $data,
        ]);
    }

    /**
     * Get all shares for the authenticated user.
     */
    public function getUserShares(Request $request): JsonResponse
    {
        $shares = ShareToken::where('user_id', $request->user()->id)
            ->with('shareable')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($share) {
                return [
                    'id' => $share->id,
                    'token' => $share->token,
                    'share_url' => $this->generateShareUrl($share),
                    'shareable_type' => $share->shareable_type_name,
                    'shareable_id' => $share->shareable_id,
                    'shareable_name' => $this->getShareableName($share->shareable),
                    'share_type' => $share->share_type,
                    'recipient_email' => $share->recipient_email,
                    'expires_at' => $share->expires_at?->toIso8601String(),
                    'is_expired' => $share->isExpired(),
                    'is_active' => $share->is_active,
                    'view_count' => $share->view_count,
                    'last_viewed_at' => $share->last_viewed_at?->toIso8601String(),
                    'created_at' => $share->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'message' => 'Shares retrieved successfully.',
            'data' => $shares,
        ]);
    }

    /**
     * Deactivate (revoke) a share.
     */
    public function revokeShare(Request $request, int $id): JsonResponse
    {
        $share = ShareToken::findOrFail($id);

        if ($share->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You do not have permission to revoke this share.',
            ], 403);
        }

        $share->deactivate();

        return response()->json([
            'message' => 'Share link revoked successfully.',
        ]);
    }

    /**
     * Delete a share token.
     */
    public function deleteShare(Request $request, int $id): JsonResponse
    {
        $share = ShareToken::findOrFail($id);

        if ($share->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You do not have permission to delete this share.',
            ], 403);
        }

        $share->delete();

        return response()->json([
            'message' => 'Share deleted successfully.',
        ]);
    }

    /**
     * Resend email for an existing share.
     */
    public function resendEmail(Request $request, int $id): JsonResponse
    {
        $share = ShareToken::findOrFail($id);

        if ($share->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You do not have permission to resend this share.',
            ], 403);
        }

        if ($share->share_type !== ShareToken::TYPE_EMAIL || empty($share->recipient_email)) {
            return response()->json([
                'message' => 'This share was not created for email delivery.',
            ], 400);
        }

        if (!$share->isValid()) {
            return response()->json([
                'message' => 'Cannot resend email for an expired or inactive share.',
            ], 400);
        }

        Mail::to($share->recipient_email)->send(
            new ShareNotification($share, $request->user())
        );
        $share->update(['email_sent_at' => now()]);

        return response()->json([
            'message' => 'Email resent successfully.',
        ]);
    }

    /**
     * Generate the share URL.
     */
    private function generateShareUrl(ShareToken $shareToken): string
    {
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));
        return rtrim($frontendUrl, '/') . '/shared/' . $shareToken->token;
    }

    /**
     * Get the name of the shareable resource.
     */
    private function getShareableName($resource): ?string
    {
        if (!$resource) {
            return null;
        }

        if ($resource instanceof Debt) {
            return $resource->debtor_name;
        }

        if ($resource instanceof Lending) {
            return $resource->borrower_name;
        }

        return null;
    }

    /**
     * Format the shared resource for the response.
     */
    private function formatSharedResource($resource, ShareToken $shareToken): array
    {
        $sharedBy = $shareToken->user;

        $base = [
            'type' => $shareToken->shareable_type_name,
            'shared_by' => $sharedBy ? $sharedBy->name : 'Unknown',
            'shared_at' => $shareToken->created_at->toIso8601String(),
            'expires_at' => $shareToken->expires_at?->toIso8601String(),
        ];

        if ($resource instanceof Debt) {
            return array_merge($base, [
                'debtor_name' => $resource->debtor_name,
                'debtor_phone' => $resource->debtor_phone,
                'debtor_email' => $resource->debtor_email,
                'total_amount' => (float) $resource->total_amount,
                'paid_amount' => (float) $resource->paid_amount,
                'remaining_amount' => $resource->remaining_amount,
                'progress_percentage' => $resource->progress_percentage,
                'description' => $resource->description,
                'priority' => $resource->priority,
                'priority_label' => $resource->priority_label,
                'payment_type' => $resource->payment_type,
                'installment_amount' => $resource->installment_amount ? (float) $resource->installment_amount : null,
                'due_date' => $resource->due_date?->toDateString(),
                'start_date' => $resource->start_date?->toDateString(),
                'status' => $resource->status,
            ]);
        }

        if ($resource instanceof Lending) {
            return array_merge($base, [
                'borrower_name' => $resource->borrower_name,
                'borrower_phone' => $resource->borrower_phone,
                'borrower_email' => $resource->borrower_email,
                'amount' => (float) $resource->amount,
                'remaining_amount' => (float) $resource->remaining_amount,
                'total_received' => $resource->total_received,
                'progress_percentage' => $resource->progress_percentage,
                'currency' => $resource->currency,
                'description' => $resource->description,
                'lending_date' => $resource->lending_date?->toDateString(),
                'expected_return_date' => $resource->expected_return_date?->toDateString(),
                'status' => $resource->status,
                'is_overdue' => $resource->is_overdue,
                'notes' => $resource->notes,
            ]);
        }

        return $base;
    }
}
