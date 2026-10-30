<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * Every active category with its sub-categories, each leading to the
     * open requests filed under it.
     */
    public function __invoke(): View
    {
        $categories = Category::query()
            ->main()
            ->busiestFirst()
            ->with(['children' => fn (HasMany $query) => $query->where('is_active', true)])
            ->get();

        return view('categories.index', [
            'categories' => $categories,
            'menuCategories' => $categories->take(SplashController::MENU_CATEGORIES),
        ]);
    }
}
