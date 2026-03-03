<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\ActivityLogService;

class LogActivity
{
    /**
     * Map of route patterns to [module, action_description] for key actions only.
     */
    protected array $trackedActions = [
        // Expenses
        'POST|expenses'          => ['expenses', 'created', 'Created an expense'],
        'DELETE|expenses'        => ['expenses', 'deleted', 'Deleted an expense'],

        // Income
        'POST|income'            => ['income', 'created', 'Set income'],
        'PUT|income'             => ['income', 'updated', 'Updated income'],

        // Debts
        'POST|debts'             => ['debts', 'created', 'Created a debt'],
        'DELETE|debts'           => ['debts', 'deleted', 'Deleted a debt'],
        'POST|debts/*/payments'  => ['debts', 'payment', 'Recorded a debt payment'],

        // Lendings
        'POST|lendings'              => ['lendings', 'created', 'Created a lending'],
        'DELETE|lendings'            => ['lendings', 'deleted', 'Deleted a lending'],
        'POST|lendings/*/payments'   => ['lendings', 'payment', 'Recorded a lending payment'],
        'POST|lendings/*/forgive'    => ['lendings', 'forgive', 'Forgave a lending'],

        // Targets
        'POST|targets'               => ['targets', 'created', 'Created a target'],
        'DELETE|targets'             => ['targets', 'deleted', 'Deleted a target'],
        'POST|targets/*/purchase'    => ['targets', 'purchase', 'Purchased a target'],

        // Balance
        'POST|balance/add'           => ['balance', 'add_money', 'Added money to balance'],

        // Settings
        'PUT|settings/profile'       => ['settings', 'update_profile', 'Updated profile'],
        'PUT|settings/preferences'   => ['settings', 'update_preferences', 'Updated preferences'],
        'PUT|settings/password'      => ['settings', 'change_password', 'Changed password'],

        // Export
        'GET|export/csv'             => ['export', 'csv', 'Exported CSV'],
        'GET|export/pdf'             => ['export', 'pdf', 'Exported PDF'],
        'GET|export/excel'           => ['export', 'excel', 'Exported Excel'],

        // Shares
        'POST|shares'                => ['shares', 'created', 'Created a share'],

        // Common Expenses
        'POST|common-expenses'           => ['common_expenses', 'created', 'Created a common expense template'],
        'POST|common-expenses/*/apply'   => ['common_expenses', 'applied', 'Applied a common expense template'],

        // Categories
        'POST|categories'            => ['categories', 'created', 'Created a category'],
        'DELETE|categories'          => ['categories', 'deleted', 'Deleted a category'],

        // Friends
        'POST|friends/request'       => ['friends', 'request_sent', 'Sent a friend request'],
    ];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only log on successful responses (2xx)
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300 && $request->user()) {
            $this->logIfTracked($request);
        }

        return $response;
    }

    protected function logIfTracked(Request $request): void
    {
        $method = $request->method();
        $path = $request->path(); // e.g. "api/expenses" or "api/debts/5/payments"

        // Strip "api/" prefix
        $path = preg_replace('#^api/#', '', $path);

        foreach ($this->trackedActions as $pattern => $info) {
            [$patternMethod, $patternPath] = explode('|', $pattern, 2);

            if ($method !== $patternMethod) {
                continue;
            }

            // Convert wildcard pattern to regex
            $regex = '#^' . str_replace('*', '[^/]+', preg_quote($patternPath, '#')) . '$#';

            if (preg_match($regex, $path)) {
                [$module, $action, $description] = $info;

                ActivityLogService::log(
                    $request->user()->id,
                    $action,
                    $module,
                    $description,
                    $request
                );

                return; // Log only one match per request
            }
        }
    }
}
