<?php

namespace App\Http\Requests;

use App\Services\PenguranganSampahService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CapaianKpiRequest extends FormRequest
{
    // Kolom sort => label (header tabel & info export); berlaku di dalam kecamatan/kelurahan, Kab/Kota & Kecamatan selalu A–Z
    public const SORT = [
        'desa' => 'Wilayah / PTS',
        'ketaatan' => 'Ketaatan Pemilahan (%)',
        'pengelolaan' => 'Pengelolaan Sampah (%)',
        'penurunan' => 'Penurunan Timbulan Sampah (%)',
        'baseline' => 'Baseline',
        'mahasiswa' => 'Mahasiswa',
        'dpl' => 'DPL',
        'klaster' => 'Klaster',
    ];

    // Pilihan jumlah baris per halaman; 0 = semua
    public const PER_PAGE = [10, 25, 50, 100, 0];

    public const PER_PAGE_DEFAULT = 25;

    // Akses dijaga middleware role di route
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bulan' => ['nullable', 'regex:/^('.PenguranganSampahService::SEMUA_BULAN.'|\d{4}-(0[1-9]|1[0-2]))$/'],
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(array_keys(self::SORT))],
            'dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE)],
        ];
    }
}
