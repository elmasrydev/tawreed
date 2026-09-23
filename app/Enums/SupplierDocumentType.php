<?php

namespace App\Enums;

enum SupplierDocumentType: string
{
    case CommercialRegistration = 'commercial_registration';
    case TaxCard = 'tax_card';
    case Logo = 'logo';
    case Portfolio = 'portfolio';

    public function label(): string
    {
        return __("enums.supplier_document_type.{$this->value}");
    }

    /**
     * Documents the verification team must see before approving a supplier.
     *
     * @return array<int, self>
     */
    public static function required(): array
    {
        return [self::CommercialRegistration, self::TaxCard];
    }
}
