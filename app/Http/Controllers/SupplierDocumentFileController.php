<?php

namespace App\Http\Controllers;

use App\Models\SupplierDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplierDocumentFileController extends Controller
{
    /**
     * Streams a legal document from its private disk, inline so PDFs and
     * images open in the browser.
     */
    public function __invoke(Request $request, SupplierDocument $supplierDocument): StreamedResponse
    {
        Gate::authorize('view', $supplierDocument);

        $media = $supplierDocument->getFirstMedia('file');

        abort_if($media === null, 404);

        return $media->toInlineResponse($request);
    }
}
