<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

// Export of all DPL/Mahasiswa rows matching the table's search, filters and order
class TableExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            ...self::pageRules(),
            // Mahasiswa table filters (read by MahasiswaController::listQuery)
            'lokasi_status' => ['nullable', 'in:sudah,belum'],
            'ketua_kelompok' => ['nullable', 'in:0,1'],
        ];
    }

    // Shared with UserExportRequest; order_col is whitelisted by the controller/export (unknown -> default order)
    public static function pageRules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'order_col' => ['nullable', 'string', 'max:50'],
            'order_dir' => ['nullable', 'in:asc,desc'],
        ];
    }
}
