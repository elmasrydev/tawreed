<?php

namespace App\Http\Controllers\Supplier;

use App\Actions\Supplier\UpdateSupplierCoverage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\UpdateCoverageRequest;
use Illuminate\Http\RedirectResponse;

class CoverageController extends Controller
{
    public function update(UpdateCoverageRequest $request, UpdateSupplierCoverage $updateCoverage): RedirectResponse
    {
        $updateCoverage->handle($request->user(), $request->validated());

        return redirect()
            ->route('supplier.account', ['tab' => 'coverage'])
            ->with('status', __('supplier.coverage_saved'));
    }
}
