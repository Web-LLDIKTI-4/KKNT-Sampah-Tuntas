<?php

namespace App\Http\Requests;

use App\Services\PenguranganSampahService;
use Illuminate\Foundation\Http\FormRequest;

class CapaianKpiDetailRequest extends FormRequest
{
    // Akses dijaga middleware role di route
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'desa' => ['required', 'string', 'max:36', 'exists:desa,id_desa'],
            'kodept' => ['required', 'string', 'max:10', 'exists:ref_satuanpendidikan,npsn'],
            'bulan' => ['nullable', 'regex:/^('.PenguranganSampahService::SEMUA_BULAN.'|\d{4}-(0[1-9]|1[0-2]))$/'],
        ];
    }
}
