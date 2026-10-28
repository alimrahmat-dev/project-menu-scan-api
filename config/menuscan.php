<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Identitas Restoran
    |--------------------------------------------------------------------------
    */
    'restaurant' => [
        'name' => env('RESTAURANT_NAME', 'Rasa Nusantara'),
        'tagline' => env('RESTAURANT_TAGLINE', 'Cita rasa Indonesia, dipesan dalam sekali pindai.'),
        'address' => env('RESTAURANT_ADDRESS', 'Jl. Cikini Raya No. 42, Jakarta Pusat'),
        'phone' => env('RESTAURANT_PHONE', '+62 812 3456 7890'),
        'open_hours' => env('RESTAURANT_OPEN_HOURS', '10.00 - 22.00'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pajak & Biaya Layanan (dalam persen)
    |--------------------------------------------------------------------------
    */
    'tax_percent' => (float) env('ORDER_TAX_PERCENT', 10),
    'service_percent' => (float) env('ORDER_SERVICE_PERCENT', 5),

    /*
    |--------------------------------------------------------------------------
    | URL Frontend (dipakai untuk membuat tautan QR meja)
    |--------------------------------------------------------------------------
    */
    'frontend_url' => rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/'),
];
