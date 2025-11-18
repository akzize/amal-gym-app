<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TraineeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $trainees = [
            [
                'full_name' => 'Ahmed Zaki Hassan',
                'full_arabic_name' => 'أحمد زكي حسن',
                'gender' => 'male',
                'dob' => '1995-07-15',
                'phone' => '0661234567',
                'address' => '12 Rue Al Amal, Casablanca, Morocco',
            ],
            [
                'full_name' => 'Fatima Zahra El Alami',
                'full_arabic_name' => 'فاطمة الزهراء العلمي',
                'gender' => 'female',
                'dob' => '1998-03-22',
                'phone' => '0522987654',
                'address' => 'Appartement 5, Immeuble B, Quartier Gueliz, Marrakech, Morocco',
            ],
            [
                'full_name' => 'Khalid Benjelloun',
                'full_arabic_name' => 'خالد بنجلون',
                'gender' => 'male',
                'dob' => '1993-11-01',
                'phone' => '0670112233',
                'address' => 'Résidence Yasmina, Avenue Mohammed V, Rabat, Morocco',
            ],
            [
                'full_name' => 'Samira Daoudi',
                'full_arabic_name' => 'سميرة الداودي',
                'gender' => 'female',
                'dob' => '1999-01-30',
                'phone' => '0537445566',
                'address' => 'Hay Salam, Rue 10, N° 45, Sale, Morocco',
            ],
            [
                'full_name' => 'Youssef Slaoui',
                'full_arabic_name' => 'يوسف السلاوي',
                'gender' => 'male',
                'dob' => '1997-09-10',
                'phone' => '0666001122',
                'address' => 'Boulevard Hassan II, Immeuble 3, Fes, Morocco',
            ],
            [
                'full_name' => 'Nadia Cherkaoui',
                'full_arabic_name' => 'نادية الشرقاوي',
                'gender' => 'female',
                'dob' => '2000-05-19',
                'phone' => '0539123456',
                'address' => 'Quartier Administratif, Rue 8, Tanger, Morocco',
            ],
            [
                'full_name' => 'Driss El Filali',
                'full_arabic_name' => 'إدريس الفيلالي',
                'gender' => 'male',
                'dob' => '1994-02-28',
                'phone' => '0660998877',
                'address' => 'Lotissement Al Qods, Bloc C, N° 12, Agadir, Morocco',
            ],
            [
                'full_name' => 'Meryem Bennis',
                'full_arabic_name' => 'مريم بنّيس',
                'gender' => 'female',
                'dob' => '1996-12-05',
                'phone' => '0524776655',
                'address' => 'Avenue Zerktouni, Résidence Safaa, Kenitra, Morocco',
            ],
            [
                'full_name' => 'Tariq Mansouri',
                'full_arabic_name' => 'طارق المنصوري',
                'gender' => 'male',
                'dob' => '1992-04-12',
                'phone' => '0664554433',
                'address' => 'Rue Oued Ziz, Hay Mohammadi, Meknes, Morocco',
            ],
            [
                'full_name' => 'Amal Ait Benhaddou',
                'full_arabic_name' => 'أمل آيت بن حدو',
                'gender' => 'female',
                'dob' => '2001-08-08',
                'phone' => '0530112233',
                'address' => 'Douar Jdid, N° 50, Ouarzazate, Morocco',
            ],
            [
                'full_name' => 'Hicham Kadiri',
                'full_arabic_name' => 'هشام القادري',
                'gender' => 'male',
                'dob' => '1995-10-25',
                'phone' => '0665102030',
                'address' => 'Quartier Industriel, Lot 10, Tetouan, Morocco',
            ],
            [
                'full_name' => 'Laila Hajji',
                'full_arabic_name' => 'ليلى حاجي',
                'gender' => 'female',
                'dob' => '1997-06-14',
                'phone' => '0523456789',
                'address' => 'Avenue des FAR, Immeuble Azur, Khouribga, Morocco',
            ],
            [
                'full_name' => 'Anas El Mahdi',
                'full_arabic_name' => 'أنس المهدي',
                'gender' => 'male',
                'dob' => '1993-03-03',
                'phone' => '0671908070',
                'address' => 'Rue Prince My Abdellah, El Jadida, Morocco',
            ],
            [
                'full_name' => 'Zineb Razi',
                'full_arabic_name' => 'زينب الراضي',
                'gender' => 'female',
                'dob' => '1998-11-20',
                'phone' => '0534210987',
                'address' => 'Hay El Wahda, Bloc D, N° 21, Settat, Morocco',
            ],
            [
                'full_name' => 'Mohamed Karim',
                'full_arabic_name' => 'محمد كريم',
                'gender' => 'male',
                'dob' => '1996-01-01',
                'phone' => '0663776655',
                'address' => 'Avenue Moulay Rachid, Safi, Morocco',
            ],
            [
                'full_name' => 'Hind Bouazza',
                'full_arabic_name' => 'هند بوعزة',
                'gender' => 'female',
                'dob' => '2000-07-29',
                'phone' => '0521665544',
                'address' => 'Quartier Al Massira, Rue 3, Oujda, Morocco',
            ],
            [
                'full_name' => 'Said Cherif',
                'full_arabic_name' => 'سعيد الشريف',
                'gender' => 'male',
                'dob' => '1994-05-17',
                'phone' => '0672887766',
                'address' => 'Lotissement Essaada, N° 15, Larache, Morocco',
            ],
            [
                'full_name' => 'Soukaina Jebbari',
                'full_arabic_name' => 'سكينة الجباري',
                'gender' => 'female',
                'dob' => '1999-02-09',
                'phone' => '0535998877',
                'address' => 'Avenue Mohammed VI, Immeuble F, Beni Mellal, Morocco',
            ],
            [
                'full_name' => 'Hamza Akkari',
                'full_arabic_name' => 'حمزة العكاري',
                'gender' => 'male',
                'dob' => '1997-04-04',
                'phone' => '0662345678',
                'address' => 'Rue d\'Alger, N° 100, Khemisset, Morocco',
            ],
            [
                'full_name' => 'Aicha El Houssaini',
                'full_arabic_name' => 'عائشة الحسيني',
                'gender' => 'female',
                'dob' => '1995-08-27',
                'phone' => '0520876543',
                'address' => 'Centre Ville, Avenue Principale, Tiznit, Morocco',
            ],
        ];

        foreach ($trainees as $trainee) {
            \App\Models\Trainee::create([...$trainee, 'image' => null]);
        }
    }
}
