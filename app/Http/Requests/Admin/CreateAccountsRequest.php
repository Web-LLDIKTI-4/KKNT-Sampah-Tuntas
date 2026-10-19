<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\AjaxFormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

// Bulk account creation for mahasiswa (user/insert) and DPL (user/insertuser)
class CreateAccountsRequest extends AjaxFormRequest
{
    private const MAX_LISTED = 10;

    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function role(): string
    {
        return str_ends_with($this->path(), 'insertuser') ? 'dpl' : 'mahasiswa';
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $emails = collect((array) $this->input('createuser', []))
            ->filter(fn ($email) => is_string($email))
            ->map(fn ($email) => mb_strtolower(trim($email)))
            ->filter()->unique()->values()->all();

        $this->merge(['createuser' => $emails]);
    }

    public function rules(): array
    {
        return [
            'createuser' => ['required', 'array', 'max:1000'],
            'createuser.*' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'createuser.required' => 'user harus dipilih',
            'createuser.max' => 'Maksimal 1000 user per proses.',
            'createuser.*.email' => 'Format email tidak valid.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $invalid = $this->invalidEmails($validator->getData()['createuser']);
            if ($invalid) {
                $label = $this->role() === 'dpl' ? 'DPL' : 'mahasiswa';
                // Escaped: the message is rendered as HTML by toastr
                $listed = implode(', ', array_map('e', array_slice($invalid, 0, self::MAX_LISTED)));
                $more = count($invalid) > self::MAX_LISTED ? ' dan '.(count($invalid) - self::MAX_LISTED).' lainnya' : '';
                $validator->errors()->add('createuser', "Tidak ada user yang dibuat. Email tidak cocok dengan data {$label} "
                    ."(tidak ditemukan, NIM/NIDN/nama kosong, PT tidak valid, atau email ganda): {$listed}{$more}");
            }
        }];
    }

    // Emails without exactly one complete source record (valid PT) or also present in the other role's table
    private function invalidEmails(array $emails): array
    {
        [$table, $other, $idColumn] = $this->role() === 'dpl'
            ? ['dpl', 'mahasiswa', 'nidn']
            : ['mahasiswa', 'dpl', 'nim'];

        // One grouped scan per table instead of a correlated subquery per row
        $counts = fn (string $from) => DB::table($from)
            ->whereIn(DB::raw('LOWER(TRIM(email))'), $emails)
            ->groupByRaw('LOWER(TRIM(email))')
            ->selectRaw('LOWER(TRIM(email)) as normalized, COUNT(*) as total')
            ->pluck('total', 'normalized')
            ->map(fn ($total) => (int) $total)
            ->all();
        $own = $counts($table);
        $others = $counts($other);

        $valid = DB::table("{$table} as p")
            ->join('ref_satuanpendidikan as sp', 'sp.npsn', '=', 'p.kodept')
            ->whereIn('p.email', $emails)
            ->whereRaw("TRIM(COALESCE(p.{$idColumn}, '')) <> ''")
            ->whereRaw("TRIM(COALESCE(p.nama, '')) <> ''")
            ->pluck('p.email')
            ->map(fn ($email) => mb_strtolower(trim($email)))
            // Normalized compare so leading spaces/case variants count as the same person
            ->filter(fn ($email) => ($own[$email] ?? 0) === 1 && ! isset($others[$email]))
            ->all();

        return array_values(array_diff($emails, $valid));
    }

    // Keep the endpoint contract: HTTP 200 + "error" key read by listmember/listdpl
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'error' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ]));
    }
}
