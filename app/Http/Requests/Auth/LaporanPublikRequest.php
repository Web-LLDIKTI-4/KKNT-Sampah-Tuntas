<?php

namespace App\Http\Requests\Auth;

use App\Models\PenguranganSampah;
use App\Services\PenguranganSampahService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class LaporanPublikRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Hanya bulan yang ada datanya, agar kombinasi key cache terbatas
            'bulan' => ['nullable', 'date_format:Y-m', Rule::in($this->bulanTersedia())],
            'kecamatan' => ['nullable', 'uuid', 'exists:kecamatan,id_kecamatan'],
            'desa' => [
                'nullable', 'uuid',
                Rule::exists('desa', 'id_desa')->when($this->input('kecamatan'), fn ($rule, $id) => $rule->where('id_kecamatan', $id)),
            ],
            'klaster' => ['nullable', 'string', Rule::in(array_keys(PenguranganSampah::KLASTER))],
        ];
    }

    private function bulanTersedia(): array
    {
        return Cache::remember(PenguranganSampah::PUBLIC_BULAN_CACHE_KEY, now()->addMinutes(10), fn () => app(PenguranganSampahService::class)->bulanList()->all());
    }

    public function filter(): array
    {
        return [
            'bulan' => $this->validated('bulan'),
            'id_kecamatan' => $this->validated('kecamatan'),
            'id_desa' => $this->validated('desa'),
            'klaster' => $this->validated('klaster'),
        ];
    }
}
