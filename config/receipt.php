<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Receipt Paper Width
    |--------------------------------------------------------------------------
    |
    | Width of the thermal printer paper roll used for payment receipts.
    | Common values are "80mm" and "58mm".
    |
    */

    'paper_width' => env('RECEIPT_PAPER_WIDTH', '80mm'),

];
