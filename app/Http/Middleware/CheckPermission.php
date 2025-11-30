<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Spatie\Permission\Exceptions\UnauthorizedException;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|null  $permission
     * @param  string|null  $guard
     */
    public function handle(Request $request, Closure $next, $permission = null, $guard = null): Response
    {
        $authGuard = $guard ?? 'sanctum';

        if (auth($authGuard)->guest()) {
            throw UnauthorizedException::notLoggedIn();
        }

        if ($permission) {
            $permissions = is_array($permission)
                ? $permission
                : explode('|', $permission);

            foreach ($permissions as $permission) {
                if (auth($authGuard)->user()->can($permission)) {
                    return $next($request);
                }
            }

            throw UnauthorizedException::forPermissions($permissions);
        }

        return $next($request);
    }
}
