<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class LaporanBulananRequest extends AjaxFormRequest
{
    public const MIN_WORDS = 200;

    protected array $htmlFields = ['deskripsi'];

    public function rules(): array
    {
        return [
            'deskripsi' => ['required', 'string', 'max:65000'],
            'bulan' => ['required', 'integer', 'between:1,12'],
            'tahun' => ['required', 'integer', 'between:2000,2100'],
            'tautan' => ['nullable', 'url:http,https', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $words = str_word_count(strip_tags((string) $this->input('deskripsi')));
            if ($words < self::MIN_WORDS) {
                $validator->errors()->add('deskripsi', 'Deskripsi minimal '.self::MIN_WORDS.' kata!');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'deskripsi.required' => 'Deskripsi harus di isi.',
            'bulan.required' => 'Bulan harus di isi.',
            'tahun.required' => 'Tahun harus di isi.',
            'tautan.url' => 'Tautan harus berupa URL http/https yang valid.',
        ];
    }
}
