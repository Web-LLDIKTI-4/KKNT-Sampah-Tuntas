<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AjaxFormRequest;

// Single destroy for mahasiswa/destroy & dpl/destroy
class DestroyPersonRequest extends AjaxFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [$this->keyName() => ['required', 'uuid']];
    }

    public function messages(): array
    {
        return [
            $this->keyName().'.required' => 'Data harus dipilih.',
            $this->keyName().'.uuid' => 'Data tidak valid.',
        ];
    }

    private function keyName(): string
    {
        return str_starts_with($this->path(), 'dpl/') ? 'id_dpl' : 'id_mahasiswa';
    }
}
