<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Plan;
use App\Models\Rfq;
use App\Models\Testimonial;
use App\ViewModels\AnonymousRfq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SplashController extends Controller
{
    /**
     * Categories shown before "view all"; the busiest ones come first.
     */
    public const VISIBLE_CATEGORIES = 8;

    /**
     * Categories listed in the nav menu before "All categories".
     */
    public const MENU_CATEGORIES = 5;

    /**
     * Open requests listed under the hero; fills six rows of two.
     */
    public const RECENT_RFQS = 12;

    /**
     * The public landing page, or straight to your own area when already
     * signed in. Every number and list on it comes from the database; a
     * section with no data behind it is hidden.
     */
    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($user = $request->user()) {
            return redirect()->route($user->role->homeRoute());
        }

        $plans = Plan::query()->active()->get();

        $recentRfqs = Rfq::query()
            ->browsable()
            ->with(AnonymousRfq::RELATIONS)
            ->latest('published_at')
            ->latest('id')
            ->limit(self::RECENT_RFQS)
            ->get();

        $latestRfq = $recentRfqs->first();

        $categories = Category::query()->main()->busiestFirst()->get();

        return view('splash', [
            'plans' => $plans,
            'featuredRfq' => $latestRfq ? AnonymousRfq::make($latestRfq) : null,
            'featuredRfqDeadline' => $latestRfq?->quote_deadline,
            'categories' => $categories,
            'menuCategories' => $categories->take(self::MENU_CATEGORIES),
            'recentRfqs' => $recentRfqs->map(fn (Rfq $rfq): AnonymousRfq => AnonymousRfq::make($rfq)),
            'visibleCategories' => self::VISIBLE_CATEGORIES,
            'testimonials' => Testimonial::query()->published()->limit(3)->get(),
        ]);
    }
}
