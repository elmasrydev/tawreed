<?php

return [
    'user_role' => [
        'buyer' => 'مشترٍ',
        'supplier' => 'مورّد',
        'admin' => 'مدير',
    ],
    'user_status' => [
        'active' => 'نشط',
        'suspended' => 'موقوف',
    ],
    'verification_status' => [
        'pending' => 'قيد المراجعة',
        'verified' => 'موثّق',
        'rejected' => 'مرفوض',
        'suspended' => 'موقوف',
    ],
    'verification_status_hint' => [
        'pending' => 'يراجع الفريق مستنداتك خلال 48 ساعة',
        'verified' => 'يظهر للمشترين بشارة «مورّد موثّق»',
        'rejected' => 'مستند غير واضح — أعد رفع السجل التجاري',
        'suspended' => 'تواصل مع الدعم لإعادة التفعيل',
    ],
    'document_status' => [
        'pending' => 'قيد المراجعة',
        'accepted' => 'مقبول',
        'rejected' => 'مرفوض',
    ],
    'supplier_document_type' => [
        'commercial_registration' => 'السجل التجاري',
        'tax_card' => 'البطاقة الضريبية',
        'logo' => 'شعار الشركة',
        'portfolio' => 'سابقة الأعمال',
    ],
    'supply_type' => [
        'one_time' => 'مرة واحدة',
        'recurring' => 'توريد دوري',
    ],
    'vat_mode' => [
        'included' => 'مشمولة',
        'excluded' => 'غير مشمولة',
    ],
    'rfq_status' => [
        'draft' => 'مسودة',
        'open' => 'نشط',
        'awarded' => 'تم التعاقد',
        'closed' => 'مغلق',
        'expired' => 'منتهٍ',
        'removed' => 'محذوف',
    ],
    'quote_status' => [
        'pending' => 'قيد المراجعة',
        'shortlisted' => 'قائمة مختصرة',
        'selected' => 'تم الاختيار',
        'rejected' => 'مرفوض',
        'withdrawn' => 'مسحوب',
        'expired' => 'منتهي الصلاحية',
        'not_selected' => 'لم يتم اختياره',
    ],
    'subscription_status' => [
        'trial' => 'فترة تجريبية',
        'active' => 'نشط',
        'expired' => 'منتهٍ',
        'cancelled' => 'ملغى',
    ],
    'message_type' => [
        'text' => 'رسالة',
        'file' => 'ملف',
        'system' => 'إشعار النظام',
        'sample_request' => 'طلب عينة',
    ],
    'report_status' => [
        'open' => 'مفتوح',
        'removed' => 'تمت الإزالة',
        'dismissed' => 'تم التجاهل',
    ],
    'report_type' => [
        'non_compliant_quote' => 'عرض سعر مخالف',
        'duplicate_rfq' => 'طلب مكرر',
        'inappropriate_content' => 'محتوى غير لائق',
        'contact_details_bypass' => 'محاولة تجاوز المنصة',
        'other' => 'أخرى',
    ],
];
