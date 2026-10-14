<?php

namespace App\Http\Controllers\Supplier;

use App\Actions\Supplier\UploadSupplierDocument;
use App\Enums\SupplierDocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\UploadDocumentRequest;
use Illuminate\Http\RedirectResponse;

class DocumentController extends Controller
{
    public function store(UploadDocumentRequest $request, UploadSupplierDocument $uploadDocument): RedirectResponse
    {
        $uploadDocument->handle(
            $request->user()->supplierProfile,
            SupplierDocumentType::from($request->validated('type')),
            $request->file('file'),
        );

        return redirect()
            ->route('supplier.account', ['tab' => 'documents'])
            ->with('status', __('supplier.document_uploaded'));
    }
}
