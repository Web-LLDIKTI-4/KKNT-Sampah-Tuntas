<?php
namespace App\Imports;

use App\Models\Mahasiswa;
use App\Models\LokasiProgram;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\Importable;

class ImportMahasiswa implements ToModel, WithStartRow
{
    use Importable;

    public array $errors = [];   // <-- tampung pesan error di sini
    public int $imported = 0;

    public function model(array $row)
    {
        $existingMahasiswa = Mahasiswa::where('nim', $row[0])
                                        ->where('kodept', $row[5])
                                        ->first();
        $existingEmailMahasiswa = Mahasiswa::where('email', $row[3])->first();

        if ($existingMahasiswa || $existingEmailMahasiswa) {
            $this->errors[] = "Baris NIM {$row[0]}: duplikat entri, dilewati.";
            return null;
        }

        $lokasiProgram = LokasiProgram::where('nama_lokasi', $row[7])->first();
        if (!$lokasiProgram) {
            $this->errors[] = "Baris NIM {$row[0]}: Lokasi Program \"{$row[7]}\" tidak ditemukan.";
            return null;
        }

        $this->imported++;

        return new Mahasiswa([
            'nim'               => $row[0],
            'tahun_masuk'       => $row[1],
            'nama'              => $row[2],
            'email'             => $row[3],
            'prodi'             => $row[4],
            'kodept'            => $row[5],
            'phone'             => $row[6],
            'location_program'  => $lokasiProgram->id,
        ]);
    }

    public function startRow(): int
    {
        return 2;
    }
}