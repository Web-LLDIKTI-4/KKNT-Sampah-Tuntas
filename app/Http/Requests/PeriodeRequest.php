<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class PeriodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tahun' => ['required', 'integer', 'between:2000,2100'],
            'bulan' => ['required', 'integer', 'between:1,12'],
        ];
    }

    // [awal, akhir] bulan terpilih (Y-m-d) untuk whereBetween di kolom DATE
    public function dateRange(): array
    {
        $start = Carbon::create((int) $this->validated('tahun'), (int) $this->validated('bulan'), 1);

        return [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()];
    }
}
