<?php
namespace App\Imports;

use App\Models\Mahasiswa;
use App\Models\LokasiProgram;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class ImportMahasiswa implements ToModel, WithStartRow, WithCalculatedFormulas
{
    use Importable;

    public array $errors = [];
    public int $imported = 0;

    // index kolom => label yang tampil di pesan error
    private array $requiredColumns = [
        0 => 'NIM',
        1 => 'Tahun Masuk',
        2 => 'Nama',
        3 => 'Email',
        4 => 'Prodi',
        5 => 'Kode PT',
        6 => 'No HP',
        7 => 'Lokasi Program',
    ];

    public function model(array $row)
    {
        $rowNumber = $row[0] ?? '(tidak diketahui)'; // buat referensi pesan error

        // 1. Validasi kolom wajib tidak kosong
        $missing = $this->findEmptyColumns($row);
        if (!empty($missing)) {
            $this->errors[] = "Baris NIM {$rowNumber}: kolom " . implode(', ', $missing) . " kosong, dilewati.";
            return null;
        }

        $email = $this->cleanEmail($row[3]);
        $nim   = $this->cleanText($row[0]);
        $phone = $this->cleanText($row[6]);

        // Cek duplikat, sebutkan kolom yang sudah terdaftar
        $duplicates = array_filter([
            "NIM {$nim}" => Mahasiswa::where('nim', $nim)->where('kodept', $row[5]),
            "Email {$email}" => Mahasiswa::where('email', $email),
            "No HP {$phone}" => Mahasiswa::where('phone', $phone),
        ], fn ($query) => $query->exists());

        if ($duplicates) {
            $this->errors[] = "Baris NIM {$nim}: ".implode(', ', array_keys($duplicates)).' sudah terdaftar, dilewati.';
            return null;
        }

        $lokasiProgram = LokasiProgram::where('nama_lokasi', $this->cleanText($row[7]))->first();
        if (!$lokasiProgram) {
            $this->errors[] = "Baris NIM {$nim}: Lokasi Program \"{$row[7]}\" tidak ditemukan.";
            return null;
        }

        $this->imported++;

        return new Mahasiswa([
            'nim'               => $nim,
            'tahun_masuk'       => $row[1],
            'nama'              => $this->cleanText($row[2]),
            'email'             => $email,
            'prodi'             => $row[4],
            'kodept'            => $row[5],
            'phone'             => $this->cleanText($row[6]),
            'location_program'  => $lokasiProgram->id,
        ]);
    }

    /**
     * Cek kolom wajib yang kosong (null, string kosong, atau cuma whitespace).
     * Return array label kolom yang kosong.
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

    private function cleanText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Fallback: kalau masih berupa rumus mentah, misal =UPPER("SANTI")
        // atau =UPPER("Jamaludin Rosyidin"), ambil isi di dalam tanda kutip.
        if (preg_match('/^=\s*UPPER\(\s*"(.*)"\s*\)$/is', trim($value), $matches)) {
            $value = $matches[1];
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