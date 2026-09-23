<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chats and buyer details are subscriber-only. A lapsed supplier keeps every
 * record but is redirected to the plans page rather than shown a 403.
 */
class EnsureSupplierSubscribed
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->hasActiveSubscription()) {
            return redirect()
                ->route('supplier.subscription')
                ->with('status', __('subscription.locked_notice'));
        }

        return $next($request);
    }
}
