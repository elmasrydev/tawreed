<?php

use App\Enums\SupplierDocumentType;
use App\Models\SupplierDocument;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Registration used to save the company logo as a legal document, while
     * every public page reads the profile's own logo. Each logo moves onto its
     * profile (unless the profile already has one) and the document row goes.
     * A file that cannot be moved is reported and its row left in place.
     */
    public function up(): void
    {
        SupplierDocument::query()
            ->where('type', SupplierDocumentType::Logo->value)
            ->with(['supplierProfile.media', 'media'])
            ->eachById(function (SupplierDocument $document): void {
                $profile = $document->supplierProfile;
                $file = $document->getFirstMedia('file');

                try {
                    if ($file && ! $profile->hasMedia('logo')) {
                        $file->move($profile, 'logo');
                    }

                    $document->delete();
                } catch (Throwable $exception) {
                    report($exception);
                }
            });
    }

    public function down(): void
    {
        //
    }
};
