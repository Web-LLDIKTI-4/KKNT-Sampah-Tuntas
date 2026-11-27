<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\RekapSampahRequest;
use App\Models\PenguranganSampah;
use App\Services\PenguranganSampahService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

// Data Sampah di landing: bulan dibatasi ke bulan yang ada datanya agar kombinasi key cache terbatas
class DataSampahPublikRequest extends RekapSampahRequest
{
    public function rules(): array
    {
        $bulanTersedia = Cache::remember(PenguranganSampah::PUBLIC_BULAN_CACHE_KEY, now()->addMinutes(10), fn () => app(PenguranganSampahService::class)->bulanList()->all());

        return ['bulan' => ['nullable', 'string', Rule::in(['semua', ...$bulanTersedia])]] + parent::rules();
    }
}
