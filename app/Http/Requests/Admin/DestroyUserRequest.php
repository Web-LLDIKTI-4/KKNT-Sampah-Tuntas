<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AjaxFormRequest;
use Illuminate\Validation\Rule;

class DestroyUserRequest extends AjaxFormRequest
{
    public function authorize(): bool
    {
        // Admin tidak boleh menghapus akunnya sendiri
        return $this->user()?->role === 'admin' && $this->input('id') !== $this->user()->id;
    }

    public function rules(): array
    {
        return [
            // Akun admin tidak bisa dihapus lewat menu ini
            'id' => ['required', 'uuid', Rule::exists('users', 'id')->whereIn('role', ['mahasiswa', 'dpl', 'pt', 'kepala', 'pemda'])],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Pengguna harus dipilih.',
            'id.uuid' => 'Pengguna tidak valid.',
            'id.exists' => 'Pengguna tidak ditemukan.',
        ];
    }
}
