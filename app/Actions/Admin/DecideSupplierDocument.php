<?php

namespace App\Actions\Admin;

use App\Enums\DocumentStatus;
use App\Models\SupplierDocument;

class DecideSupplierDocument
{
    /**
     * Records the review of one legal document. A rejection note is shown to
     * the supplier next to the document so they know what to re-upload.
     */
    public function handle(SupplierDocument $document, DocumentStatus $status, ?string $note = null): SupplierDocument
    {
        $document->forceFill([
            'status' => $status,
            'note' => $status === DocumentStatus::Rejected ? $note : null,
        ])->save();

        return $document;
    }
}
