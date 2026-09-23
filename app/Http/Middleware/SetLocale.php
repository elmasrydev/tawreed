<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the interface language for every request. A signed-in user's saved
 * locale wins, then the session, then the application default.
 */
class SetLocale
{
    /**
     * @var array<int, string>
     */
    public const SUPPORTED = ['ar', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale
            ?? $request->session()->get('locale')
            ?? config('app.locale');

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = config('app.fallback_locale');
        }

        app()->setLocale($locale);
        $request->session()->put('locale', $locale);

        return $next($request);
    }

    /**
     * Arabic is the only right-to-left language the platform ships with.
     */
    public static function isRtl(?string $locale = null): bool
    {
        return ($locale ?? app()->getLocale()) === 'ar';
    }
}
