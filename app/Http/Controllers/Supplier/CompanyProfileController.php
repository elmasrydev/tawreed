<?php

namespace App\Http\Controllers\Supplier;

use App\Actions\Supplier\UpdateSupplierCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\UpdateCompanyProfileRequest;
use Illuminate\Http\RedirectResponse;

class CompanyProfileController extends Controller
{
    public function update(UpdateCompanyProfileRequest $request, UpdateSupplierCompany $updateCompany): RedirectResponse
    {
        $updateCompany->handle($request->user(), $request->validated());

        return redirect()
            ->route('supplier.account')
            ->with('status', __('supplier.company_saved'));
    }
}
