<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate a route on the user's role.
 *
 * Coarse area access lives here ("this is the management area"); record level
 * rules live in policies ("only an admin may edit another teacher").
 */
class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isActive()) {
            abort(403);
        }

        $allowed = array_map(
            fn (string $role): UserRole => UserRole::from($role),
            $roles,
        );

        if (! $user->hasRole(...$allowed)) {
            abort(403);
        }

        return $next($request);
    }
}
