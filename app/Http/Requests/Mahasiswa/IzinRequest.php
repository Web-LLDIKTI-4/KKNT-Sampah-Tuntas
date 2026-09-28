<?php

namespace App\Http\Requests\Mahasiswa;

use App\Http\Requests\AjaxFormRequest;
use App\Models\Kehadiran;
use App\Services\AttendanceService;
use Illuminate\Validation\Rule;

class IzinRequest extends AjaxFormRequest
{
    public function rules(): array
    {
        return [
            'status_kehadiran' => ['required', Rule::in(AttendanceService::IZIN_STATUSES)],
            'keterangan' => ['required', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            $exists = Kehadiran::ownedBy($this->user())->whereDate('tanggal', today())->exists();
            if ($exists) {
                $validator->errors()->add('tanggal', 'Data pada tanggal tersebut terisi!');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'status_kehadiran.required' => 'Jenis izin harus dipilih.',
            'status_kehadiran.in' => 'Jenis izin tidak valid.',
            'keterangan.required' => 'Keterangan harus diisi.',
        ];
    }
}
