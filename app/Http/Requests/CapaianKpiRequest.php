<?php

namespace App\Http\Requests;

use App\Services\PenguranganSampahService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CapaianKpiRequest extends FormRequest
{
    // Kolom sort => label (header tabel & info export)
    public const SORT = ['kab_kota' => 'Kabupaten/Kota', 'kecamatan' => 'Kecamatan', 'desa' => 'Kelurahan/Desa', 'klaster' => 'Klaster'];

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
        ];
    }
}
