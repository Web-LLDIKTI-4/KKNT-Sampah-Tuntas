<?php
namespace App\Imports;

use App\Models\Mahasiswa;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithValidation;

class ImportMahasiswa implements ToModel, WithStartRow
{
    use Importable;
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $existingMahasiswa = Mahasiswa::where('nim', $row[0])
                                       ->where('kodept', $row[5])
                                       ->first();
        $existingEmailMahasiswa = Mahasiswa::where('email', $row[3])->first();
        // Jika data sudah ada, Anda bisa melakukan sesuatu, seperti melewatinya
        if ($existingMahasiswa || $existingEmailMahasiswa) {
            session()->flash('message', 'Duplikat entri ditemukan. Data telah dilewati.');
            return null;
        }
        // Jika data belum ada, Anda membuat instance Mahasiswa baru
        return new Mahasiswa([
            'nim'         => $row[0],
            'tahun_masuk' => $row[1],
            'nama'      => $row[2],
            'email'        => $row[3],
            'prodi'       => $row[4],
            'kodept'       => $row[5],
            'phone'       => $row[6],
        ]);
    }
   
    public function startRow(): int
    {
        return 2; // Mulai dari baris kedua (baris 2)
    }


}