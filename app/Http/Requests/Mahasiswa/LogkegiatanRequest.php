<?php

namespace App\Http\Requests\Mahasiswa;

use App\Http\Requests\AjaxFormRequest;
use Illuminate\Validation\Rule;

class LogkegiatanRequest extends AjaxFormRequest
{
    public const FIELDS = [
        'tanggal', 'deskripsi', 'volume', 'satuan', 'id_kpi', 'tautan',
    ];

    protected array $htmlFields = ['deskripsi'];

    public function rules(): array
    {
        return [
            'id_log' => [Rule::requiredIf($this->isUpdate()), 'nullable', 'uuid'],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'deskripsi' => [
                'required', 'string', 'max:65000',
                Rule::unique('logkegiatan', 'deskripsi')
                    ->where('email', $this->user()->email)
                    ->where('tanggal', $this->input('tanggal'))
                    ->ignore($this->input('id_log'), 'id_log'),
            ],
            'volume' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'satuan' => ['required', 'string', 'max:255'],
            'id_kpi' => ['required', 'uuid', 'exists:kpi,id_kpi'],
            'tautan' => ['nullable', 'url:http,https', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'deskripsi.required' => 'Deskripsi harus di isi.',
            'deskripsi.unique' => 'Deskripsi sudah digunakan!',
            'tanggal.required' => 'Tanggal harus di isi.',
            'tanggal.before_or_equal' => 'Tanggal tidak boleh melebihi hari ini.',
            'volume.required' => 'Volume harus di isi.',
            'volume.numeric' => 'Volume harus berupa angka.',
            'satuan.required' => 'Satuan harus di isi.',
            'id_kpi.required' => 'KPI harus dipilih.',
            'tautan.url' => 'Tautan harus berupa URL http/https yang valid.',
        ];
    }
}
