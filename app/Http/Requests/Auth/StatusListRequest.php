<?php

namespace App\Http\Requests\Auth;

use App\Services\LandingStatusList;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StatusListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jenis' => ['required', 'in:mobilisasi,pelaksanaan,lokasi'],
            'status' => ['required', 'in:sudah,belum'],
            'q' => ['nullable', 'string', 'min:2', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'per_page' => ['nullable', 'integer', 'in:10,25'],
            'sort' => ['nullable', 'string', Rule::in(LandingStatusList::SORT_FIELDS[is_string($this->input('jenis')) ? $this->input('jenis') : ''] ?? [])],
            'dir' => ['nullable', 'in:asc,desc'],
        ];
    }
}
