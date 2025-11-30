<?php

return [
    // Success messages
    'success' => [
        'created' => 'تم إنشاء :resource بنجاح',
        'updated' => 'تم تحديث :resource بنجاح',
        'deleted' => 'تم حذف :resource بنجاح',
        'restored' => 'تم استعادة :resource بنجاح',
    ],

    // Error messages
    'error' => [
        'not_found' => ':resource غير موجود',
        'unauthorized' => 'غير مصرح بالوصول',
        'validation_failed' => 'فشل التحقق من البيانات',
        'server_error' => 'خطأ في الخادم',
        'delete_failed' => 'لا يمكن حذف :resource',
    ],

    // Resources
    'resources' => [
        'expense' => 'المصروف',
        'income' => 'الدخل',
        'category' => 'الفئة',
        'user' => 'المستخدم',
        'export' => 'التصدير',
    ],

    // Validation messages
    'validation' => [
        'amount_positive' => 'يجب أن يكون المبلغ موجباً',
        'amount_required' => 'المبلغ مطلوب',
        'amount_numeric' => 'يجب أن يكون المبلغ رقماً',
        'amount_min' => 'يجب أن يكون المبلغ أكبر من صفر',
        'date_past' => 'لا يمكن أن يكون التاريخ في المستقبل',
        'date_required' => 'التاريخ مطلوب',
        'date_invalid' => 'تنسيق التاريخ غير صحيح',
        'category_exists' => 'الفئة غير موجودة',
        'category_has_expenses' => 'لا يمكن حذف فئة تحتوي على مصروفات',
        'category_required' => 'الفئة مطلوبة',
        'category_invalid' => 'فئة غير صالحة',
        'income_required' => 'يرجى تعيين دخلك الشهري أولاً',
        'name_required' => 'الاسم مطلوب',
        'email_required' => 'البريد الإلكتروني مطلوب',
        'email_invalid' => 'تنسيق البريد الإلكتروني غير صحيح',
        'email_exists' => 'البريد الإلكتروني موجود بالفعل',
        'password_required' => 'كلمة المرور مطلوبة',
        'password_min' => 'يجب أن تتكون كلمة المرور من 8 أحرف على الأقل',
        'password_confirmed' => 'تأكيد كلمة المرور غير متطابق',
        'icon_required' => 'الأيقونة مطلوبة',
        'color_required' => 'اللون مطلوب',
        'color_invalid' => 'تنسيق اللون غير صحيح. استخدم التنسيق السداسي عشري (مثال: #FF5733)',
    ],

    // Authentication
    'auth' => [
        'login_success' => 'تم تسجيل الدخول بنجاح',
        'logout_success' => 'تم تسجيل الخروج بنجاح',
        'register_success' => 'تم التسجيل بنجاح',
        'invalid_credentials' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة',
        'invalid_current_password' => 'كلمة المرور الحالية غير صحيحة',
        'token_expired' => 'انتهت صلاحية الرمز',
    ],

    // Dashboard
    'dashboard' => [
        'monthly_income' => 'الدخل الشهري',
        'total_expenses' => 'إجمالي المصروفات',
        'remaining_balance' => 'الرصيد المتبقي',
        'spending_percentage' => 'نسبة الإنفاق',
        'top_category' => 'أعلى فئة إنفاق',
    ],

    // Export
    'export' => [
        'success' => 'تم تصدير البيانات بنجاح',
        'no_data' => 'لا توجد بيانات متاحة للتصدير',
        'invalid_format' => 'تنسيق تصدير غير صالح',
    ],

    // Income
    'income' => [
        'created' => 'تم تعيين الدخل الشهري بنجاح',
        'updated' => 'تم تحديث الدخل الشهري بنجاح',
        'deleted' => 'تم حذف سجل الدخل بنجاح',
        'no_income' => 'لم يتم تعيين الدخل بعد',
    ],

    // Expense
    'expense' => [
        'created' => 'تم إنشاء المصروف بنجاح',
        'updated' => 'تم تحديث المصروف بنجاح',
        'deleted' => 'تم حذف المصروف بنجاح',
    ],

    // Category
    'category' => [
        'created' => 'تم إنشاء الفئة بنجاح',
        'updated' => 'تم تحديث الفئة بنجاح',
        'deleted' => 'تم حذف الفئة بنجاح',
        'cannot_update_default' => 'لا يمكن تحديث الفئات الافتراضية',
        'cannot_delete_default' => 'لا يمكن حذف الفئات الافتراضية',
        'has_expenses' => 'لا يمكن حذف فئة تحتوي على مصروفات',
    ],

    // Settings
    'settings' => [
        'profile_updated' => 'تم تحديث الملف الشخصي بنجاح',
        'password_changed' => 'تم تغيير كلمة المرور بنجاح. يرجى تسجيل الدخول مرة أخرى.',
    ],
];
