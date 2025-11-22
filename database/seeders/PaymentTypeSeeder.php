<?php

namespace Database\Seeders;

use App\Models\PaymentType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentTypes = [
            [
                'name' => 'monthly',
                'name_ar' => 'شهري',
                'duration_months' => 1,
                'description' => 'Access for one month',
            ],
            [
                'name' => 'quarterly',
                'name_ar' => 'ربع سنوي',
                'duration_months' => 3,
                'description' => 'Access for three months',
            ],
            [
                'name' => 'yearly',
                'name_ar' => 'سنوي',
                'duration_months' => 12,
                'description' => 'Access for one year',
            ],

            [
                'name' => 'inscription',
                'name_ar' => 'التسجيل',
                'duration_months' => 12,
                'description' => 'inscription fee',
            ],
            [
                'name' => 'daily_session',
                'name_ar' => 'حصة يومية',
                'description' => 'personal training session',
            ],
            [
                'name' => 'insurance',
                'name_ar' => 'التأمين',
                'description' => 'personal training weekly session',
                'duration_months' => 6,
            ],
            [
                'name' => 'custom',
                'name_ar' => 'مخصص',
                'description' => 'Custom payment type',
            ]
        ];

        foreach ($paymentTypes as $type) {
            PaymentType::create($type);
        }
    }
}
