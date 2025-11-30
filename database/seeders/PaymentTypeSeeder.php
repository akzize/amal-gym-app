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
                'name' => 'Monthly',
                'name_ar' => 'شهري',
                'duration_months' => 1,
                'description' => 'Access for one month',
            ],
            [
                'name' => 'Yearly',
                'name_ar' => 'سنوي',
                'duration_months' => 12,
                'description' => 'Access for one year',
            ],

            [
                'name' => 'Inscription',
                'name_ar' => 'التسجيل',
                'duration_months' => 12,
                'description' => 'inscription fee',
            ],
            [
                'name' => 'Daily Session',
                'name_ar' => 'حصة يومية',
                'description' => 'personal training session',
            ],
            [
                'name' => 'Insurance',
                'name_ar' => 'التأمين',
                'description' => 'personal training weekly session',
                'duration_months' => 6,
            ],
            [
                'name' => 'Custom',
                'name_ar' => 'مخصص',
                'description' => 'Custom payment type',
            ]
        ];

        foreach ($paymentTypes as $type) {
            PaymentType::create($type);
        }
    }
}
