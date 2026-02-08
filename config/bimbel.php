<?php

declare(strict_types=1);

return [
    'default_password' => '12345678',
    'admin_whatsapp' => env('BIMBEL_ADMIN_WHATSAPP'),
    'payment_accounts' => [
        [
            'bank' => 'BCA',
            'name' => 'Citra Megawaty',
            'number' => '3312142373',
        ],
        [
            'bank' => 'Mandiri',
            'name' => 'Citra Megawaty',
            'number' => '1440024413608',
        ],
        [
            'bank' => 'OVO',
            'name' => 'Budi Utomo',
            'number' => '081703027942',
        ],
    ],
    'class_student_placeholder' => 'Murid Kelas Bersama',
];
