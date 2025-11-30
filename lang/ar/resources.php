<?php
return [
    'notes' => 'ملاحظات',
    'navigationGroups' => [
        'management' => 'إدارة البيانات',
        'subscriptions' => 'إدارة الاشتراكات',
        'users' => 'إدارة المستخدمين',
        'settings' => 'الإعدادات',
        'gyms' => 'إدارة الصالات الرياضية',
        'students' => 'إدارة الممارسين',
        'sports' => 'إدارة الرياضات',
        'associations' => 'إدارة الجمعيات',
        'trainers' => 'إدارة المدربين',
        'training' => 'إدارة المدربين والممارسين',
        'sport_groupe' => 'إدارة المجموعات الرياضية',
        'salle_association' => 'إدارة الصالات والجمعيات',
    ],
    'group' => [
        'label' => 'المجموعة',
        'modelLabel' => 'المجموعة',
        'navigationLabel' => 'المجموعات',
        'pluralModelLabel' => 'المجموعات',
        'name' => 'اسم المجموعة',
        'email' => 'البريد الإلكتروني',
        'name_ar' => 'الاسم الكامل (بالعربية)',
        'phone' => 'الهاتف',
        'whatsap_number' => 'رقم الواتساب',
        'increment_contract_number' => 'رقم عقد الازياد',
        'avatar' => 'الصورة الشخصية',
        'address' => 'العنوان',
        'dob' => 'تاريخ الميلاد',
        'gender' => 'الجنس',
        'male' => 'ذكر',
        'female' => 'أنثى',
        'max_capacity' => 'الحد الأقصى',
    ],
    'payment' => [
        'label' => 'الدفعة',
        'modelLabel' => 'الدفعة',
        'navigationLabel' => 'الدفعات',
        'pluralModelLabel' => 'الدفعات',
        'types' => [
            'label' => 'نوع الدفعة',
            'monthly_fee' => 'الرسوم الشهرية',
            'yearly_fee' => 'الرسوم السنوية',
            'inscription' => 'رسوم التسجيل',
            'insurance' => 'التأمين',
            'free' => 'مجاني'
        ],
        'amount_paid' => 'المبلغ المدفوع',
        'payment_date' => 'تاريخ الدفع',
        'amount_due' => 'المبلغ الواجب دفعه',
        'remaining_amount' => 'المبلغ المتبقي',
        'insurance' => [
            'for_association' => 'مبلغ التأمين للجمعية',
            'net' => 'صافي مبلغ التأمين',
            'all' => 'مبلغ التأمين الكلي'

        ],
        'status' => [
            'label' => 'الحالة',
            'paid' => 'مدفوع',
            'unpaid' => 'غير مدفوع',
            'partial' => 'جزئي',
        ],
        'monthly_fee' => 'الرسوم الشهرية',
        'yearly_fee' => 'الرسوم السنوية',
        'inscription' => 'رسوم التسجيل',
        'due_date' => 'تاريخ الاستحقاق',
        'paid_at' => 'تاريخ الدفع',
        'registratin' => 'رسوم التسجيل',
        'custom_duration_months' => 'مدة الاشتراك المخصصة (بالشهور)',
        'applies_to_date' => '	تاريخ الاستحقاق'

    ],
    'subscription' => [
        'label' => 'الاشتراك',
        'modelLabel' => 'الاشتراك',
        'navigationLabel' => 'الاشتراكات',
        'pluralModelLabel' => 'الاشتراكات',
        'start_date' => 'تاريخ البداية',
        'end_date' => 'تاريخ النهاية',
        'status' => [
            'name' => 'الحالة',
            'active' => 'نشط',
            'inactive' => 'غير نشط',
            'expired' => 'منتهي الصلاحية',
        ],
    ],
    'trainee' => [
        'label' => 'ممارس', 
        'modelLabel' => 'الممارس',
        'navigationLabel' => 'الممارسين',
        'pluralModelLabel' => 'الممارسين', 
        'full_name' => 'الاسم الكامل',
        'first_name' => 'الاسم الشخصي',
        'last_name' => 'الاسم العائلي',
        'name' => 'الاسم الكامل',
        'email' => 'البريد الإلكتروني',
        'full_arabic_name' => 'الاسم الكامل (بالعربية)',
        'phone' => 'الهاتف',
        'whatsap_number' => 'رقم الواتساب',
        'increment_contract_number' => 'رقم عقد الإزدياد',
        'address' => 'العنوان',
        'dob' => 'تاريخ الميلاد',
        'role' => 'الدور',
        'avatar' => 'الصورة الشخصية',
        'group' => 'المجموعة',
        'gender' => 'الجنس',

        'actions' => [
            'export_selected_trainees' => 'تحميل المتدربين المحددين',
            'add_to_group' => 'اضافة الى المجموعة'
        ]
    ],
    'sport' => [
        'label' => 'الرياضة',
        'modelLabel' => 'الرياضة',
        'navigationLabel' => 'الرياضات',
        'pluralModelLabel' => 'الرياضات',
        'sport_name' => 'اسم الرياضة',
        'name' => 'الرياضة',
        'name_plural' => 'الرياضات',
        'name_arabic' => 'الرياضة (بالعربية)',
        'description' => 'الوصف',
        'description_arabic' => 'الوصف (بالعربية)',
    ],
    // trainer
    'trainer' => [
        'label' => 'المدرب',
        'modelLabel' => 'المدرب',
        'navigationLabel' => 'المدربين',
        'pluralModelLabel' => 'المدربين',
        'name' => 'الاسم',
        'trainer_name' => 'اسم المدرب',
        'name_arabic' => 'الاسم (بالعربية)',
        'status' => [
            'name' => 'الحالة',
            'active' => 'نشط',
            'inactive' => 'غير نشط',
            'suspended' => 'موقوف',
        ],
        'gym_salle' => 'الصالة الرياضية',
        'payment_percent' => 'نسبة الدفع',
        'price_for_each_student' => 'السعر لكل ممارس',
        "salary_type" => "نوع الراتب",
        "salary_amount" => "مبلغ الراتب",

        "login_credentials" => "بيانات تسجيل الدخول",
        "edit_login_credentials" => "تعديل بيانات تسجيل الدخول",
        "email" => "البريد الإلكتروني",
        "password" => "كلمة المرور",
        "Payment info" => "معلومات الدفع",
        "fixed" => "ثابت",
        "percentage" => "النسبة",
        "per_group" => "لكل مجموعة",
        "per_trainee" => "لكل متدرب",
        "none" => "لا يوجد",
        "active" => "نشط",
        "inactive" => "غير نشط",
        "groups" => "المجموعات",
        "notes" => "ملاحظات",

        // dashboard
        'monthly_payments' => 'مدفوعات المدربين الشهرية',
        'calculated_payout' => 'المبلغ المحسوب',
        'trainees_count' => 'عدد الممارسين',
        'groups_count' => 'عدد المجموعات',

        'trainer_payment_processing' => 'معالجة مدفوعات المدرب',
        'payment_details' => 'تفاصيل الدفع',
        'current_balance_and_payment_entry' => 'الرصيد الحالي وإدخال الدفع',
        'enter_full_or_partial_amount' => 'أدخل المبلغ بالكامل أو جزءًا منه',
        'amount_to_pay_now' => 'المبلغ المستحق دفعه الآن',
        'trainer_payment_processing' => 'معالجة مدفوعات المدرب',

    ],

    'actions' => [
        'pay_now' => 'ادفع الآن',
        'cancel' => 'إلغاء',
    ],

    'messages' => [
        'payment_recorded_successfully' => 'تم تسجيل الدفعة بنجاح',
        'payment_recorded_body' => 'تم تسجيل دفعة بقيمة :amount_paid درهم مغربي للمدرب :trainer_name.',

    ],

    'dashboard' => [
        'payments_statistics_title' => 'إحصائيات المدفوعات',
        'financial_overview_description' => 'ملخص لأبرز الإحصائيات والوضع المالي الحالي للمدربين والمدفوعات.',
        'widget_title_total_income' => 'إجمالي الدخل هذا الشهر',
        'widget_title_unpaid_balance' => 'إجمالي الرصيد غير المدفوع',
        'widget_description_income' => 'إجمالي المدفوعات التي تم جمعها في :month',

    ]
];
