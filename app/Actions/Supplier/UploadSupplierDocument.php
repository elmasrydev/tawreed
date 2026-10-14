<?php

namespace App\Actions\Supplier;

use App\Enums\DocumentStatus;
use App\Enums\SupplierDocumentType;
use App\Enums\VerificationStatus;
use App\Models\SupplierDocument;
use App\Models\SupplierProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class UploadSupplierDocument
{
    /**
     * Adds a legal document, or replaces the one of the same type. Any new file
     * needs a fresh review, and a rejected supplier who re-uploads goes back
     * into the verification queue.
     */
    public function handle(SupplierProfile $profile, SupplierDocumentType $type, UploadedFile $file): SupplierDocument
    {
        return DB::transaction(function () use ($profile, $type, $file): SupplierDocument {
            $document = $profile->documents()->firstOrNew(['type' => $type]);

            $document->forceFill([
                'status' => DocumentStatus::Pending,
                'note' => null,
            ])->save();

            $document->addMedia($file)->toMediaCollection('file');

            if ($profile->verification_status === VerificationStatus::Rejected) {
                $profile->forceFill(['verification_status' => VerificationStatus::Pending])->save();
            }

            return $document;
        });
    }
}
