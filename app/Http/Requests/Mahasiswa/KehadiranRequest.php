<?php

namespace App\Http\Requests\Mahasiswa;

use Illuminate\Foundation\Http\FormRequest;

class KehadiranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mode' => ['required', 'in:datang,pulang'],
            'latitude_datang' => ['required_if:mode,datang', 'nullable', 'numeric', 'between:-90,90'],
            'longitude_datang' => ['required_if:mode,datang', 'nullable', 'numeric', 'between:-180,180'],
            'latitude_pulang' => ['required_if:mode,pulang', 'nullable', 'numeric', 'between:-90,90'],
            'longitude_pulang' => ['required_if:mode,pulang', 'nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude_datang.required_if' => 'Lokasi Anda belum terdeteksi. Izinkan akses lokasi lalu muat ulang halaman.',
            'longitude_datang.required_if' => 'Lokasi Anda belum terdeteksi. Izinkan akses lokasi lalu muat ulang halaman.',
            'latitude_pulang.required_if' => 'Lokasi Anda belum terdeteksi. Izinkan akses lokasi lalu muat ulang halaman.',
            'longitude_pulang.required_if' => 'Lokasi Anda belum terdeteksi. Izinkan akses lokasi lalu muat ulang halaman.',
        ];
    }

    /**
     * @return array{0: float, 1: float}
     */
    public function coordinates(): array
    {
        $mode = $this->validated('mode');

        return [(float) $this->validated('latitude_'.$mode), (float) $this->validated('longitude_'.$mode)];
    }
}
