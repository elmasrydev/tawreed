<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps an account that has not confirmed its email code out of the buyer and
 * supplier areas, sending it back to the verification screen instead.
 */
class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->hasVerifiedEmail()) {
            return redirect()->route('email.verify');
        }

        return $next($request);
    }
}
