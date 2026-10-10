<?php

namespace App\Http\Requests\Auth;

use App\Services\PetaSebaranService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class PetaSebaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $options = $this->options();

        // Hanya nilai dari daftar opsi, agar kombinasi key cache terbatas
        return [
            'tahun' => ['nullable', 'integer', Rule::in($options['tahun'])],
            'kodept' => ['nullable', 'string', 'max:20', Rule::in(array_column($options['pt'], 'kodept'))],
        ];
    }

    private function options(): array
    {
        return Cache::remember(PetaSebaranService::FILTER_CACHE_KEY, now()->addMinutes(10), fn () => app(PetaSebaranService::class)->options());
    }

    public function filter(): array
    {
        return [
            'tahun' => (int) ($this->validated('tahun') ?? date('Y')),
            'kodept' => $this->validated('kodept'),
        ];
    }
}
