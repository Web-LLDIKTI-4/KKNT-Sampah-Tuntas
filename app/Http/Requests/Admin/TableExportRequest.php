<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Export of the DPL/Mahasiswa table's current view (search, filters, order, page)
class TableExportRequest extends FormRequest
{
    public const LENGTHS = [10, 25, 50, 100, 500, -1];

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
            'start' => ['nullable', 'integer', 'min:0'],
            'length' => ['nullable', 'integer', Rule::in(self::LENGTHS)],
            'q' => ['nullable', 'string', 'max:100'],
            'order_col' => ['nullable', 'string', 'max:50'],
            'order_dir' => ['nullable', 'in:asc,desc'],
        ];
    }
}
