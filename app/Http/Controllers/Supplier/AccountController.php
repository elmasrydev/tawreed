<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    /**
     * @var list<string>
     */
    private const TABS = ['company', 'coverage', 'documents'];

    public function __invoke(Request $request): View
    {
        $supplier = $request->user();

        return view('supplier.account', [
            'supplier' => $supplier,
            'profile' => $supplier->supplierProfile->load(['governorates', 'categories.parent', 'documents']),
            'tab' => in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'company',
        ]);
    }
}
