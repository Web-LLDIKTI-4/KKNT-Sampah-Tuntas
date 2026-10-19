<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AjaxFormRequest;
use App\Models\Dpl;
use App\Models\Mahasiswa;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use App\Rules\EmailNotInPersonData;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends AjaxFormRequest
{
    private ?User $target = null;

    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        // Array input would make find() return a Collection
        $this->target = is_string($this->input('id')) ? User::whereIn('role', ['mahasiswa', 'dpl'])->find($this->input('id')) : null;

        if (is_string($this->input('email'))) {
            $email = mb_strtolower(trim($this->input('email')));
            // Case-only change keeps the stored email so the link to mahasiswa/dpl stays exact
            $this->merge(['email' => $this->target && mb_strtolower($this->target->email) === $email ? $this->target->email : $email]);
        }
    }

    public function rules(): array
    {
        $current = $this->target?->email;

        return [
            // Hanya akun mahasiswa/dpl yang boleh diubah lewat form ini
            'id' => ['required', 'uuid', Rule::exists('users', 'id')->whereIn('role', ['mahasiswa', 'dpl'])],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->input('id')),
                // 1 user : 1 record; new email must not belong to another mahasiswa/DPL record
                new EmailNotInPersonData($current),
            ],
            'location_program' => ['nullable', 'uuid', 'exists:lokasi_program,id'],
            'akses' => ['nullable', Rule::in(['pjdesa', 'hapuspjdesa'])],
            'password' => ['nullable', 'string', 'max:255', Password::min(8)],
        ];
    }

    // Email change cascades via the source record; an unlinked account would end up orphaned
    public function after(): array
    {
        return [function (Validator $validator) {
            $user = $this->target;
            if (! $user || $validator->errors()->has('email') || $this->input('email') === $user->email) {
                return;
            }

            $model = $user->role === 'dpl' ? Dpl::class : Mahasiswa::class;
            if (! $model::where('email', $user->email)->exists()) {
                $validator->errors()->add('email', 'Akun ini tidak terhubung ke data '.($user->role === 'dpl' ? 'DPL' : 'mahasiswa').', email tidak dapat diubah.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'id.exists' => 'Pengguna tidak ditemukan.',
            'name.required' => 'Nama harus di isi.',
            'email.required' => 'Email harus diisi.',
            'email.email' => 'Email tidak valid.',
            'email.unique' => 'Email sudah digunakan!',
            'location_program.exists' => 'Lokasi program tidak valid.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
        ];
    }
}
