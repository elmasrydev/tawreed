<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\PortfolioItemRequest;
use App\Models\PortfolioItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PortfolioController extends Controller
{
    public function store(PortfolioItemRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $item = $request->user()->supplierProfile->portfolioItems()->create(
                $request->safe()->only(['title', 'description', 'client_type', 'year']),
            );

            $this->attachImages($item, $request->file('images', []));
        });

        return $this->backToPortfolio(__('supplier.portfolio_saved'));
    }

    public function update(PortfolioItemRequest $request, PortfolioItem $portfolioItem): RedirectResponse
    {
        Gate::authorize('update', $portfolioItem);

        DB::transaction(function () use ($request, $portfolioItem): void {
            $portfolioItem->update($request->safe()->only(['title', 'description', 'client_type', 'year']));

            $portfolioItem->getMedia('images')
                ->whereIn('id', array_map('intval', $request->validated('remove_images', [])))
                ->each->delete();

            $this->attachImages($portfolioItem, $request->file('images', []));
        });

        return $this->backToPortfolio(__('supplier.portfolio_saved'));
    }

    public function destroy(PortfolioItem $portfolioItem): RedirectResponse
    {
        Gate::authorize('delete', $portfolioItem);

        $portfolioItem->delete();

        return $this->backToPortfolio(__('supplier.portfolio_deleted'));
    }

    /**
     * @param  array<int, UploadedFile>  $images
     */
    private function attachImages(PortfolioItem $item, array $images): void
    {
        foreach ($images as $image) {
            $item->addMedia($image)->toMediaCollection('images');
        }
    }

    private function backToPortfolio(string $status): RedirectResponse
    {
        return redirect()
            ->route('supplier.account', ['tab' => 'portfolio'])
            ->with('status', $status);
    }
}
