<?php

namespace App\Imports;

use App\Models\Dpl;
use App\Models\LokasiProgram;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class DPLImport implements ToModel, WithStartRow, WithCalculatedFormulas
{
    use Importable;

    public array $errors = [];
    public int $imported = 0;

    // index kolom => label yang tampil di pesan error
    private array $requiredColumns = [
        0 => 'NIDN',
        1 => 'Nama',
        2 => 'Email',
        3 => 'Lokasi Program',
        4 => 'Prodi',
        5 => 'Kode PT',
        6 => 'No HP',
    ];

    public function model(array $row)
    {
        $nidn = $row[0] ?? '(tidak diketahui)';

        // 1. Validasi kolom wajib tidak kosong
        $missing = $this->findEmptyColumns($row);
        if (!empty($missing)) {
            $this->errors[] = "Baris NIDN {$nidn}: kolom " . implode(', ', $missing) . " kosong, dilewati.";
            return null;
        }

        $nidnClean  = $this->cleanText($row[0]);
        $namaClean  = $this->cleanText($row[1]);
        $email      = $this->cleanEmail($row[2]);
        $phone      = $this->cleanText($row[6]);

        // 2. Cek duplikat, sebutkan kolom yang sudah terdaftar
        $duplicates = array_filter([
            "NIDN {$nidnClean}" => Dpl::where('nidn', $nidnClean)->where('kodept', $row[5]),
            "Email {$email}" => Dpl::where('email', $email),
            "No HP {$phone}" => Dpl::where('phone', $phone),
        ], fn ($query) => $query->exists());

        if ($duplicates) {
            $this->errors[] = "Baris NIDN {$nidnClean}: ".implode(', ', array_keys($duplicates)).' sudah terdaftar, dilewati.';
            return null;
        }

        // 3. Cek lokasi program
        $lokasiProgram = LokasiProgram::where('nama_lokasi', $this->cleanText($row[3]))->first();
        if (!$lokasiProgram) {
            $this->errors[] = "Baris NIDN {$nidnClean}: Lokasi Program \"{$row[3]}\" tidak ditemukan.";
            return null;
        }

        $this->imported++;

        return new Dpl([
            'nidn'              => $nidnClean,
            'nama'              => $namaClean,
            'email'             => $email,
            'location_program'  => $lokasiProgram->id,
            'prodi'             => $row[4],
            'kodept'            => $row[5],
            'phone'             => $this->cleanText($row[6]),
        ]);
    }

    /**
     * Cek kolom wajib yang kosong (null, string kosong, atau cuma whitespace).
     */
    private function findEmptyColumns(array $row): array
    {
        $missing = [];

        foreach ($this->requiredColumns as $index => $label) {
            $value = $row[$index] ?? null;

            if ($value === null || trim((string) $value) === '') {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    /**
     * Bersihkan whitespace, newline, dan fallback rumus =UPPER("...") yang kebawa mentah.
     */
    private function cleanText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Fallback: kalau masih berupa rumus mentah, misal =UPPER("SANTI")
        if (preg_match('/^=\s*UPPER\(\s*"(.*)"\s*\)$/is', trim($value), $matches)) {
            $value = strtoupper($matches[1]);
        }

        $value = preg_replace('/[\r\n\t]+/', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        return trim($value);
    }

    private function cleanEmail(?string $value): ?string
    {
        $value = $this->cleanText($value);
        return $value !== null ? str_replace(' ', '', $value) : null;
    }

    public function startRow(): int
    {
        return 2;
    }
}
