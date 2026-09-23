/* TawreedHub runtime i18n — swaps text nodes / placeholders EN⇄AR via dictionaries, flips dir. */
(function () {
  const ORIG = new WeakMap(); // node -> original English text
  const norm = (t) => t.replace(/\s+/g, ' ').trim();
  const AR_FONT = "'IBM Plex Sans Arabic', Inter, system-ui, sans-serif";
  const ATTRS = ['placeholder', 'title', 'aria-label'];

  const COMMON = {
    // nav / generic
    'Dashboard': 'لوحة التحكم', 'Overview': 'نظرة عامة', 'Settings': 'الإعدادات', 'Messages': 'الرسائل', 'Notifications': 'الإشعارات', 'Profile': 'الملف الشخصي', 'Log out': 'تسجيل الخروج', 'Log in': 'تسجيل الدخول', 'Search': 'بحث', 'Filter': 'تصفية', 'Filters': 'التصفية', 'All': 'الكل', 'Save': 'حفظ', 'Save changes': 'حفظ التغييرات', 'Cancel': 'إلغاء', 'Back': 'رجوع', 'Continue': 'متابعة', 'Next': 'التالي', 'Done': 'تم', 'Edit': 'تعديل', 'Delete': 'حذف', 'View': 'عرض', 'View all': 'عرض الكل', 'Close': 'إغلاق', 'Send': 'إرسال', 'Submit': 'إرسال', 'Apply': 'تطبيق', 'Reset': 'إعادة تعيين', 'Yes': 'نعم', 'No': 'لا', 'Optional': 'اختياري', 'Required': 'مطلوب', 'Export CSV': 'تصدير CSV', 'Download': 'تحميل', 'Upload': 'رفع', 'Remove': 'إزالة', 'Details': 'التفاصيل', 'Actions': 'إجراءات', 'Status': 'الحالة', 'Date': 'التاريخ', 'Time': 'الوقت', 'Amount': 'المبلغ', 'Total': 'الإجمالي', 'Category': 'الفئة', 'Categories': 'الفئات', 'Governorate': 'المحافظة', 'Governorates': 'المحافظات', 'Quantity': 'الكمية', 'Deadline': 'الموعد النهائي', 'Delivery': 'التسليم', 'Payment': 'الدفع', 'Price': 'السعر', 'Unit price': 'سعر الوحدة', 'Notes': 'ملاحظات', 'Today': 'اليوم', 'Yesterday': 'أمس', 'now': 'الآن', 'today': 'اليوم', 'tomorrow': 'غدًا', 'yesterday': 'أمس', 'or': 'أو', 'and': 'و', 'EN': 'EN', 'Help': 'مساعدة', 'Terms': 'الشروط', 'Privacy': 'الخصوصية', 'Terms of Service': 'شروط الخدمة', 'Terms of service': 'شروط الخدمة', 'Privacy Policy': 'سياسة الخصوصية', 'Privacy policy': 'سياسة الخصوصية', 'Contact': 'اتصل بنا', 'About': 'من نحن', 'Careers': 'الوظائف', 'Blog': 'المدونة', 'Company': 'الشركة', 'Product': 'المنتج', 'Legal': 'قانوني', 'Pricing': 'الأسعار', 'FAQ': 'الأسئلة الشائعة', 'How it works': 'كيف يعمل', 'Get started': 'ابدأ الآن', 'EGP': 'ج.م', 'kg': 'كجم', 'Verified': 'موثّق', 'Pending': 'قيد الانتظار', 'Active': 'نشط', 'Expired': 'منتهي', 'Suspended': 'موقوف', 'Rejected': 'مرفوض', 'Approved': 'مقبول', 'Open': 'مفتوح', 'Closed': 'مغلق', 'Awarded': 'تم الترسية', 'Draft': 'مسودة', 'Sent': 'مُرسل', 'Submitted': 'مُقدَّم', 'Shortlisted': 'في القائمة المختصرة', 'Declined': 'مرفوض', 'Lost': 'لم يُرسَ', 'Paid': 'مدفوع', 'Failed': 'فشل', 'Refunded': 'مُسترد', 'Hidden': 'مخفي', 'Flagged': 'مُعلَّم', 'Closing soon': 'يُغلق قريبًا', 'In review': 'قيد المراجعة', 'Resolved': 'تم الحل', 'Unverified': 'غير موثّق', 'Published': 'منشور', 'Removed': 'محذوف',
    // roles / entities
    'Buyer': 'مشتري', 'Buyers': 'المشترون', 'Supplier': 'مورّد', 'Suppliers': 'المورّدون', 'RFQ': 'طلب عرض سعر', 'RFQs': 'طلبات عروض الأسعار', 'Quote': 'عرض سعر', 'Quotes': 'عروض الأسعار', 'My RFQs': 'طلباتي', 'My Quotes': 'عروضي', 'Browse RFQs': 'استعراض الطلبات', 'Create RFQ': 'إنشاء طلب', 'Post an RFQ': 'انشر طلب عرض سعر', 'Submit Quote': 'تقديم عرض سعر', 'Submit quote': 'تقديم عرض سعر', 'Subscription': 'الاشتراك', 'Subscriptions': 'الاشتراكات', 'Verification': 'التوثيق', 'Verified supplier': 'مورّد موثّق', 'Verified suppliers': 'مورّدون موثّقون', 'Zero commission': 'صفر عمولة', 'Commission': 'عمولة',
    // plans / payments
    '15 days': '15 يومًا', '1 month': 'شهر واحد', '1 year': 'سنة واحدة', 'Fawry': 'فوري', 'Bank transfer': 'تحويل بنكي', 'Vodafone Cash': 'فودافون كاش', 'Vodafone Cash / InstaPay': 'فودافون كاش / إنستاباي', 'Debit / credit card': 'بطاقة خصم / ائتمان', 'Net 30': 'آجل 30 يومًا', 'Net 15': 'آجل 15 يومًا', 'Cash': 'نقدًا',
    // categories
    'Food & beverage': 'أغذية ومشروبات', 'Fresh produce': 'خضار وفاكهة', 'Frozen meat & poultry': 'لحوم ودواجن مجمدة', 'Chilled meat': 'لحوم مبردة', 'Dairy & eggs': 'ألبان وبيض', 'Dry goods & grains': 'بقالة وحبوب', 'Dry goods': 'بقالة', 'Beverages': 'مشروبات', 'Seafood': 'أسماك ومأكولات بحرية', 'Packaging': 'تغليف', 'Cleaning & hygiene': 'تنظيف ونظافة', 'Construction materials': 'مواد بناء', 'Office supplies': 'مستلزمات مكتبية', 'Medical supplies': 'مستلزمات طبية', 'Raw materials': 'مواد خام', 'Hotel amenities': 'مستلزمات فندقية', 'Equipment rental': 'تأجير معدات', 'Textiles & uniforms': 'منسوجات وأزياء موحدة',
    // supplier / buyer types
    'Manufacturer': 'مصنّع', 'Farm / local producer': 'مزرعة / منتج محلي', 'Importer': 'مستورد', 'Distributor': 'موزّع', 'Wholesaler': 'تاجر جملة', 'Equipment / contracting': 'معدات / مقاولات', 'Hotel / resort': 'فندق / منتجع', 'Restaurant / café': 'مطعم / كافيه', 'Restaurant chain': 'سلسلة مطاعم', 'Contractor': 'مقاول', 'Retail chain': 'سلسلة تجزئة', 'Hospital / clinic': 'مستشفى / عيادة', 'Factory': 'مصنع', 'Facility management': 'إدارة مرافق', 'Facility mgmt': 'إدارة مرافق', 'School / university': 'مدرسة / جامعة', 'Individual': 'فرد', 'Other': 'أخرى',
    // governorates
    'Cairo': 'القاهرة', 'Giza': 'الجيزة', 'Alexandria': 'الإسكندرية', 'Beheira': 'البحيرة', 'Kafr El Sheikh': 'كفر الشيخ', 'Gharbia': 'الغربية', 'Dakahlia': 'الدقهلية', 'Sharqia': 'الشرقية', 'Qalyubia': 'القليوبية', 'Monufia': 'المنوفية', 'Red Sea': 'البحر الأحمر', 'South Sinai': 'جنوب سيناء', 'North Sinai': 'شمال سيناء', 'Suez': 'السويس', 'Ismailia': 'الإسماعيلية', 'Port Said': 'بورسعيد', 'Fayoum': 'الفيوم', 'Beni Suef': 'بني سويف', 'Minya': 'المنيا', 'Assiut': 'أسيوط', 'Sohag': 'سوهاج', 'Qena': 'قنا', 'Luxor': 'الأقصر', 'Aswan': 'أسوان', 'Matrouh': 'مطروح', 'New Valley': 'الوادي الجديد', 'Damietta': 'دمياط', 'Sharm El Sheikh': 'شرم الشيخ', 'El Gouna': 'الجونة', 'Egypt': 'مصر',
    // frequency
    'Daily': 'يوميًا', 'Weekly': 'أسبوعيًا', 'Monthly': 'شهريًا', 'One-off': 'مرة واحدة',
  };

  function translateNode(node, lang, dict) {
    if (node.nodeType === 3) {
      const raw = node.nodeValue;
      if (!raw || !norm(raw)) return;
      let orig = ORIG.get(node);
      const prevTr = orig !== undefined ? (dict[norm(orig)] || COMMON[norm(orig)]) : undefined;
      if (orig === undefined || (raw !== orig && raw !== prevTr)) { orig = raw; ORIG.set(node, raw); } // React changed the source text
      if (lang === 'ar') {
        const key = norm(orig);
        const hit = dict[key] || COMMON[key];
        if (hit && node.nodeValue !== hit) node.nodeValue = hit;
        else if (!hit && node.nodeValue !== orig) node.nodeValue = orig;
      } else if (node.nodeValue !== orig) node.nodeValue = orig;
      return;
    }
    if (node.nodeType !== 1) return;
    const el = node;
    const tag = (el.tagName || '').toUpperCase(); if (tag === 'SCRIPT' || tag === 'STYLE' || tag === 'SVG' || el.hasAttribute('data-no-i18n')) return;
    for (const a of ATTRS) {
      if (!el.hasAttribute(a)) continue;
      const k = '__i18n_' + a;
      if (el[k] === undefined) el[k] = el.getAttribute(a);
      const orig = el[k];
      const want = lang === 'ar' ? (dict[norm(orig)] || COMMON[norm(orig)] || orig) : orig;
      if (el.getAttribute(a) !== want) el.setAttribute(a, want);
    }
    for (const c of el.childNodes) translateNode(c, lang, dict);
  }

  const observers = new WeakMap();
  function apply(root, lang, dict) {
    if (!root) return;
    dict = dict || {};
    const st = observers.get(root) || {};
    st.lang = lang; st.dict = Object.assign(st.dict || {}, dict);
    observers.set(root, st);
    root.setAttribute('dir', lang === 'ar' ? 'rtl' : 'ltr');
    root.setAttribute('lang', lang);
    if (lang === 'ar') { if (st.font === undefined) st.font = root.style.fontFamily; root.style.fontFamily = AR_FONT; } else if (st.font !== undefined) root.style.fontFamily = st.font;
    const run = () => { translateNode(root, st.lang, st.dict); if (st.obs) st.obs.takeRecords(); }; // drop records caused by our own writes
    run();
    if (!st.obs) {
      // Only translate the nodes that changed (cheap), synchronously so English never paints; skip while lang is 'en'.
      st.obs = new MutationObserver((recs) => {
        if (st.inRun || st.lang !== 'ar') return;
        st.inRun = true;
        try {
          for (const r of recs) {
            if (r.type === 'characterData') translateNode(r.target, st.lang, st.dict);
            else for (const n of r.addedNodes) translateNode(n, st.lang, st.dict);
          }
          st.obs.takeRecords();
        } finally { st.inRun = false; }
      });
      st.timer = setInterval(run, 1500); // insurance against missed mutations
      st.obs.observe(root, { childList: true, subtree: true, characterData: true });
    }
  }
  window.TH_I18N = { apply, COMMON, norm };
})();
