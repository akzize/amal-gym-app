<?php

/*
 * What is printed on the insurance bordereau (the Word export), besides the trainees.
 * The values are the ones of the federation's form.
 */
return [
    'bordereau' => [
        'federation' => 'F.R.M Karaté & D.A',
        'association' => 'AMAL POUR LE Développement Sportive',
        'address' => 'HAY FEDRAGOUM EL TAMASSINTE OUARZAZATE',
        'league' => 'DARAA DU KARATé & D.A',
        'phone' => '0624836434',
        'contract' => '18/61969',
        'bank' => 'Crédit agricole',
        'bank_account' => '225 810 035 403 228 651 0114 86',
        // lines on one page of the form; more trainees continue on another page
        'rows_per_page' => 25,
        // images of the form, in public/
        'images' => [
            'insurer' => 'assets/insurace/wafaassurance_image_both_sides.png',
            'federation' => 'assets/insurace/frmk_middle_image.png',
            'karate' => 'assets/insurace/karate_guy.png',
        ],
    ],
];
