<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Rfq;
use App\ViewModels\AnonymousRfq;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicRfqController extends Controller
{
    /**
     * The open requests anyone can search from the landing page. Buyer
     * identity is stripped by the view model, and specs and attachments
     * stay behind a supplier login.
     */
    public function __invoke(Request $request): View
    {
        $term = $request->string('q')->trim()->toString();

        $category = $request->filled('category')
            ? Category::query()->where('is_active', true)->where('slug', $request->string('category'))->first()
            : null;

        $rfqs = Rfq::query()
            ->browsable()
            ->with(AnonymousRfq::RELATIONS)
            ->when($term, fn ($query) => $query->search($term))
            ->when($category, fn ($query) => $query->inCategory($category))
            ->latest('published_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $categories = Category::query()->main()->busiestFirst()->get();

        return view('rfqs.index', [
            'rfqs' => $rfqs,
            'items' => $rfqs->map(fn (Rfq $rfq): AnonymousRfq => AnonymousRfq::make($rfq)),
            'term' => $term,
            'category' => $category,
            'categories' => $categories,
            'menuCategories' => $categories->take(SplashController::MENU_CATEGORIES),
        ]);
    }
}
