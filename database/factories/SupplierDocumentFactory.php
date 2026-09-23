<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Enums\SupplierDocumentType;
use App\Models\SupplierDocument;
use App\Models\SupplierProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierDocument>
 */
class SupplierDocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_profile_id' => SupplierProfile::factory(),
            'type' => SupplierDocumentType::CommercialRegistration,
            'status' => DocumentStatus::Pending,
        ];
    }
}
