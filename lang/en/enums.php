<?php

return [
    'user_role' => [
        'buyer' => 'Buyer',
        'supplier' => 'Supplier',
        'admin' => 'Admin',
    ],
    'user_status' => [
        'active' => 'Active',
        'suspended' => 'Suspended',
    ],
    'verification_status' => [
        'pending' => 'Under review',
        'verified' => 'Verified',
        'rejected' => 'Rejected',
        'suspended' => 'Suspended',
    ],
    'verification_status_hint' => [
        'pending' => 'Our team reviews your documents within 48h',
        'verified' => 'Buyers see your "Verified Supplier" badge',
        'rejected' => 'Unclear document — re-upload the commercial registration',
        'suspended' => 'Contact support to reactivate',
    ],
    'document_status' => [
        'pending' => 'Pending',
        'accepted' => 'Accepted',
        'rejected' => 'Rejected',
    ],
    'supplier_document_type' => [
        'commercial_registration' => 'Commercial registration',
        'tax_card' => 'Tax card',
        'logo' => 'Company logo',
        'portfolio' => 'Portfolio',
    ],
    'supply_type' => [
        'one_time' => 'One-time',
        'recurring' => 'Recurring',
    ],
    'vat_mode' => [
        'included' => 'Included',
        'excluded' => 'Excluded',
    ],
    'rfq_status' => [
        'draft' => 'Draft',
        'open' => 'Active',
        'awarded' => 'Awarded',
        'closed' => 'Closed',
        'expired' => 'Expired',
        'removed' => 'Removed',
    ],
    'quote_status' => [
        'pending' => 'Pending',
        'shortlisted' => 'Shortlisted',
        'selected' => 'Selected',
        'rejected' => 'Rejected',
        'withdrawn' => 'Withdrawn',
        'expired' => 'Expired',
        'not_selected' => 'Not selected',
    ],
    'subscription_status' => [
        'trial' => 'Trial',
        'active' => 'Active',
        'expired' => 'Expired',
        'cancelled' => 'Cancelled',
    ],
    'message_type' => [
        'text' => 'Message',
        'file' => 'File',
        'system' => 'System notice',
        'sample_request' => 'Sample request',
    ],
    'report_status' => [
        'open' => 'Open',
        'removed' => 'Content removed',
        'dismissed' => 'Dismissed',
    ],
    'report_type' => [
        'non_compliant_quote' => 'Non-compliant quote',
        'duplicate_rfq' => 'Duplicate RFQ',
        'inappropriate_content' => 'Inappropriate content',
        'contact_details_bypass' => 'Platform bypass attempt',
        'other' => 'Other',
    ],
];
