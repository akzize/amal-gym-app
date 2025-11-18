<?php

namespace Database\Seeders;

use App\Models\Sport;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // sport types
        $selfDefenseSports = [
            "Karate" => [
                "arabic" => "كاراتيه",
                "price" => 50
            ],
            "Taekwondo" => [
                "arabic" => "تايكوندو",
                "price" => 50
            ],
            "Brazilian Jiu-Jitsu" => [
                "arabic" => "جيو جيتسو البرازيلية",
                "price" => 50
            ],
            "Krav Maga" => [
                "arabic" => "كراف ماغا",
                "price" => 50
            ],
            "Muay Thai" => [
                "arabic" => "مواي تاي",
                "price" => 50
            ],
            "Judo" => [
                "arabic" => "جودو",
                "price" => 50
            ],
            "Boxing" => [
                "arabic" => "ملاكمة",
                "price" => 50
            ],
            "Kickboxing" => [
                "arabic" => "كيك بوكسينغ",
                "price" => 50
            ],
            "MMA" => [
                "arabic" => "فنون القتال المختلطة",
                "price" => 50
            ],
            "Aikido" => [
                "arabic" => "أيكيدو",
                "price" => 50
            ],
            "Kendo" => [
                "arabic" => "كيندو",
                "price" => 50
            ],
            "Savate" => [
                "arabic" => "سافات",
                "price" => 50
            ]
        ];

        // create sport types
        foreach ($selfDefenseSports as $name => $data) {
            Sport::create([
                "name" => $name,
                "name_ar" => $data["arabic"],
            ]);
        }
    }
}
