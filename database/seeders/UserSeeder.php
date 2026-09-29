<?php

namespace Database\Seeders;

use App\Models\Desa;
use App\Models\Dpl;
use App\Models\Dplmentoring;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    // Satu akun dummy per role
    public function run(string $password): void
    {
        $lokasi = LokasiProgram::orderBy('nama_lokasi')->firstOrFail();
        $pt = Satuanpendidikan::firstOrFail();
        $desa = Desa::firstOrFail();

        User::factory()->role('admin')->withPassword($password)->create([
            'name' => 'Administrator',
            'email' => 'admin@kknt.test',
        ]);

        User::factory()->role('kepala')->withPassword($password)->create([
            'name' => 'Kepala',
            'email' => 'kepala@kknt.test',
        ]);

        // Akun PT login memakai NPSN sebagai email
        User::factory()->role('pt')->withPassword($password)->create([
            'name' => $pt->nm_lemb,
            'email' => $pt->npsn,
            'location_program' => $lokasi->id,
        ]);

        $dpl = Dpl::factory()->create([
            'email' => 'dpl@kknt.test',
            'kodept' => $pt->npsn,
            'location_program' => $lokasi->id,
        ]);
        User::factory()->role('dpl')->withPassword($password)->create([
            'name' => $dpl->nama,
            'email' => $dpl->email,
            'location_program' => $lokasi->id,
        ]);

        // Mahasiswa sekaligus ketua kelompok agar fitur capaian KPI bisa dicoba
        $mahasiswa = Mahasiswa::factory()->create([
            'email' => 'mahasiswa@kknt.test',
            'kodept' => $pt->npsn,
            'location_program' => $lokasi->id,
        ]);
        User::factory()->role('mahasiswa')->withPassword($password)->create([
            'name' => $mahasiswa->nama,
            'email' => $mahasiswa->email,
            'location_program' => $lokasi->id,
            'akses' => 'pjdesa',
        ]);

        Mahasiswa_lokasi::create([
            'tahun' => (int) date('Y'),
            'id_mahasiswa' => $mahasiswa->id_mahasiswa,
            'id_desa' => $desa->id_desa,
            'user_in_up' => $mahasiswa->email,
        ]);
        Dplmentoring::create([
            'email_mahasiswa' => $mahasiswa->email,
            'email_dpl' => $dpl->email,
        ]);
        Pjdesa::create(['email' => $mahasiswa->email, 'id_desa' => $desa->id_desa]);
    }
}
