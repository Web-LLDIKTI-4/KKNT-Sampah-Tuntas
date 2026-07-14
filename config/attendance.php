<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Radius Absensi
    |--------------------------------------------------------------------------
    |
    | Jarak maksimal (dalam meter) dari salah satu titik koordinat lokasi
    | yang diizinkan untuk melakukan absensi (datang/pulang).
    |
    */
    'radius_meter' => 100,

    /*
    |--------------------------------------------------------------------------
    | Titik Koordinat Lokasi (Multi Koordinat)
    |--------------------------------------------------------------------------
    |
    | Daftar titik koordinat yang valid untuk absensi. Mahasiswa dianggap
    | berada di lokasi yang sah jika berada dalam radius_meter dari SALAH SATU
    | titik koordinat berikut (dihitung berdasarkan jarak terdekat).
    |
    */
    'locations' => [
        [
            'nama' => 'LLDIKTI Wilayah IV',
            'latitude' => -6.8992479713514285,
            'longitude' => 107.63771992191431,
        ],
        // [
        //     'nama' => 'Kantor Saya',
        //     'latitude' => -6.833644,
        //     'longitude' => 108.247755,
        // ],
    ],
];
