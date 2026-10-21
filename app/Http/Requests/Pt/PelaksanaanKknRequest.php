<?php

namespace App\Http\Requests\Pt;

use App\Http\Requests\AjaxFormRequest;
use App\Models\PelaksanaanKkn;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class PelaksanaanKknRequest extends AjaxFormRequest
{
    public const MIN_TANGGAL = '2020-01-01';

    public const MAX_DURASI_HARI = 366;

    public function authorize(): bool
    {
        return (bool) $this->user()?->can('upsert', PelaksanaanKkn::class);
    }

    public function rules(): array
    {
        return [
            'tanggal_mulai' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.self::MIN_TANGGAL, 'before_or_equal:'.$this->maxTanggal()],
            'tanggal_selesai' => ['required', 'date_format:Y-m-d', 'after_or_equal:tanggal_mulai', 'before_or_equal:'.$this->maxTanggal()],
        ];
    }

    // Durasi inklusif maksimal 366 hari
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['tanggal_mulai', 'tanggal_selesai'])) {
                    return;
                }

                $mulai = Carbon::createFromFormat('!Y-m-d', $this->input('tanggal_mulai'));
                $selesai = Carbon::createFromFormat('!Y-m-d', $this->input('tanggal_selesai'));

                if ((int) $mulai->diffInDays($selesai) + 1 > self::MAX_DURASI_HARI) {
                    $validator->errors()->add('tanggal_selesai', 'Durasi pelaksanaan maksimal '.self::MAX_DURASI_HARI.' hari.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal_mulai.required' => 'Tanggal mulai harus diisi.',
            'tanggal_mulai.date_format' => 'Tanggal mulai tidak valid (format hari/bulan/tahun).',
            'tanggal_mulai.after_or_equal' => 'Tanggal mulai paling awal 01-01-2020.',
            'tanggal_mulai.before_or_equal' => 'Tanggal mulai paling lambat '.$this->maxTanggalLabel().'.',
            'tanggal_selesai.required' => 'Tanggal selesai harus diisi.',
            'tanggal_selesai.date_format' => 'Tanggal selesai tidak valid (format hari/bulan/tahun).',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'tanggal_selesai.before_or_equal' => 'Tanggal selesai paling lambat '.$this->maxTanggalLabel().'.',
        ];
    }

    private function maxTanggal(): string
    {
        return (now()->year + 1).'-12-31';
    }

    private function maxTanggalLabel(): string
    {
        return '31-12-'.(now()->year + 1);
    }
}
