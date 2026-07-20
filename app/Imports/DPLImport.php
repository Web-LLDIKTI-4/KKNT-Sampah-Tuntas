<?php

namespace App\Imports;

use App\Models\Dpl;
use App\Models\LokasiProgram;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\Importable;

class DPLImport implements ToModel, WithStartRow
{
    use Importable;

    public array $errors = [];   // <-- tampung pesan error di sini
    public int $imported = 0;

    public function model(array $row)
    {
        $existingMahasiswa = Dpl::where('nidn', $row[0])
                                        ->where('kodept', $row[5])
                                        ->first();
        $existingEmailMahasiswa = Dpl::where('email', $row[2])->first();

        if ($existingMahasiswa || $existingEmailMahasiswa) {
            $this->errors[] = "Baris NIDN {$row[0]}: duplikat entri, dilewati.";
            return null;
        }

        $lokasiProgram = LokasiProgram::where('nama_lokasi', $row[3])->first();
        if (!$lokasiProgram) {
            $this->errors[] = "Baris NIDN {$row[0]}: Lokasi Program \"{$row[3]}\" tidak ditemukan.";
            return null;
        }

        $this->imported++;

        return new Dpl([
            'nidn'              => $row[0],
            'nama'              => $row[1],
            'email'             => $row[2],
            'location_program'  => $lokasiProgram->id,
            'prodi'             => $row[4],
            'kodept'            => $row[5],
            'phone'             => $row[6],
        ]);
    }

    public function startRow(): int
    {
        return 2;
    }
}
