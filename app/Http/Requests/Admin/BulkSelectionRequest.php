<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AjaxFormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

// Selected row ids for bulk actions; unknown ids are skipped by the action, not rejected here
class BulkSelectionRequest extends AjaxFormRequest
{
    public const MAX_IDS = 500;

    // Status for validation errors; export overrides (fetched as blob)
    protected int $errorStatus = 200;

    // null = first validation error
    protected ?string $errorMessage = null;

    // MySQL matches uuids case-insensitively; lowercase so keyBy/array_flip lookups match
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if (is_array($this->input('ids'))) {
            $this->merge(['ids' => array_map(fn ($id) => is_string($id) ? strtolower($id) : $id, $this->input('ids'))]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:'.self::MAX_IDS],
            'ids.*' => ['required', 'uuid', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Pilih minimal satu data.',
            'ids.array' => 'Data terpilih tidak valid.',
            'ids.min' => 'Pilih minimal satu data.',
            'ids.max' => 'Maksimal '.self::MAX_IDS.' data per aksi.',
            'ids.*.required' => 'Data terpilih tidak valid.',
            'ids.*.uuid' => 'Data terpilih tidak valid.',
            'ids.*.distinct' => 'Data terpilih ganda.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $this->errorMessage ?? $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], $this->errorStatus));
    }
}
