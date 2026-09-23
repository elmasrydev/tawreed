<?php

namespace App\Enums;

enum ReportType: string
{
    case NonCompliantQuote = 'non_compliant_quote';
    case DuplicateRfq = 'duplicate_rfq';
    case InappropriateContent = 'inappropriate_content';
    case ContactDetailsBypass = 'contact_details_bypass';
    case Other = 'other';

    public function label(): string
    {
        return __("enums.report_type.{$this->value}");
    }
}
