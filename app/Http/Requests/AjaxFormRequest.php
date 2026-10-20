<?php

namespace App\Http\Requests;

use App\Support\HtmlSanitizer;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Carbon;

/**
 * Base Form Request untuk form AJAX: error validasi dikembalikan
 * dalam format {success, message, errors} yang dibaca frontend.
 */
abstract class AjaxFormRequest extends FormRequest
{
    // Field berisi HTML dari editor; disanitasi sebelum validasi
    protected array $htmlFields = [];

    // Field kolom DATE; dinormalisasi ke Y-m-d agar rule unique tidak bisa dibypass "Y-m-d H:i:s"
    protected array $dateFields = [];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $clean = [];
        foreach ($this->htmlFields as $field) {
            if (is_string($this->input($field))) {
                $clean[$field] = HtmlSanitizer::clean($this->input($field));
            }
        }
        foreach ($this->dateFields as $field) {
            $value = $this->input($field);
            if (is_string($value) && strtotime($value) !== false) {
                $clean[$field] = Carbon::parse($value)->toDateString();
            }
        }
        $this->merge($clean);
    }

    // Route update memakai pola "<modul>/update"
    protected function isUpdate(): bool
    {
        return str_ends_with($this->path(), '/update');
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Data gagal disimpan!',
            'errors' => $validator->errors(),
        ]));
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Anda tidak memiliki akses untuk data ini.',
        ], 403));
    }
}
