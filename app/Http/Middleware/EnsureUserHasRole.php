<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the buyer and supplier areas separate. A user who lands in the wrong
 * area is redirected to their own home rather than shown a 403.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if (! in_array($user->role->value, $roles, true)) {
            return redirect()->route($user->role->homeRoute());
        }

        return $next($request);
    }

    /**
     * Convenience for route definitions: `EnsureUserHasRole::for(UserRole::Buyer)`.
     */
    public static function for(UserRole ...$roles): string
    {
        return static::class.':'.implode(',', array_map(fn (UserRole $role): string => $role->value, $roles));
    }
}
