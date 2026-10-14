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

        $latestRfq = Rfq::query()
            ->browsable()
            ->with(['buyer.buyerProfile.businessType', 'buyer.buyerProfile.governorate', 'unit', 'governorate', 'category'])
            ->latest('published_at')
            ->latest('id')
            ->first();

        return view('splash', [
            'plans' => $plans,
            'featuredRfq' => $latestRfq ? AnonymousRfq::make($latestRfq) : null,
            'featuredRfqDeadline' => $latestRfq?->quote_deadline,
            'categories' => Category::query()
                ->main()
                ->withCount(['rfqs as open_rfqs_count' => fn ($query) => $query->browsable()])
                ->get()
                ->sortByDesc('open_rfqs_count')
                ->values(),
            'visibleCategories' => self::VISIBLE_CATEGORIES,
            'testimonials' => Testimonial::query()->published()->limit(3)->get(),
        ]);
    }
}
