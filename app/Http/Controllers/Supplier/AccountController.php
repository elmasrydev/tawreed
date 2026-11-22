<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Governorate;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AccountController extends Controller
{
    /**
     * @var list<string>
     */
    private const TABS = ['company', 'coverage', 'documents', 'portfolio'];

    public function __invoke(Request $request): View
    {
        $supplier = $request->user();
        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'company';
        $profile = $supplier->supplierProfile->load(['governorates', 'categories.parent', 'documents.media', 'portfolioItems.media']);
        $isEditingCoverage = $tab === 'coverage' && $request->boolean('edit');

        return view('supplier.account', [
            'supplier' => $supplier,
            'profile' => $profile,
            'tab' => $tab,
            'isEditingCompany' => $tab === 'company' && $request->boolean('edit'),
            'isEditingCoverage' => $isEditingCoverage,
            'governorates' => $isEditingCoverage ? Governorate::query()->orderBy('sort')->get() : collect(),
            'categories' => $isEditingCoverage ? $this->selectableCategories($profile->categories) : collect(),
        ]);
    }

    /**
     * The main categories offered at registration, plus any sub-category an
     * admin assigned, so saving the form never silently drops it.
     *
     * @param  Collection<int, Category>  $assigned
     * @return Collection<int, Category>
     */
    private function selectableCategories(Collection $assigned): Collection
    {
        $main = Category::query()->main()->get();

        return $main->concat($assigned->reject(fn (Category $category): bool => $main->contains('id', $category->id))->values());
    }
}
