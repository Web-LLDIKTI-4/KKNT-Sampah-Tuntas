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
    public function run(string $password): void
    {
        $lokasi = LokasiProgram::orderBy('nama_lokasi')->get();
        $ptList = Satuanpendidikan::all();
        $desaList = Desa::all();

        User::factory()->role('admin')->withPassword($password)->create([
            'name' => 'Administrator',
            'email' => 'admin@pps.test',
        ]);

        // Akun PT login memakai NPSN sebagai email
        foreach ($ptList as $i => $pt) {
            User::factory()->role('pt')->withPassword($password)->create([
                'name' => $pt->nm_lemb,
                'email' => $pt->npsn,
                'location_program' => $lokasi[$i % $lokasi->count()]->id,
            ]);
        }

        $dplList = collect();
        foreach (range(1, 6) as $i) {
            $pt = $ptList[$i % $ptList->count()];
            $lokasiId = $lokasi[$i % $lokasi->count()]->id;
            $dpl = Dpl::factory()->create([
                'email' => "dpl{$i}@pps.test",
                'kodept' => $pt->npsn,
                'location_program' => $lokasiId,
            ]);
            User::factory()->role('dpl')->withPassword($password)->create([
                'name' => $dpl->nama,
                'email' => $dpl->email,
                'location_program' => $lokasiId,
            ]);
            $dplList->push($dpl);
        }

        foreach (range(1, 30) as $i) {
            $dpl = $dplList[$i % $dplList->count()];
            $desa = $desaList[$i % $desaList->count()];
            $isKetua = $i <= $desaList->count();

            $mahasiswa = Mahasiswa::factory()->create([
                'email' => "mhs{$i}@pps.test",
                'kodept' => $dpl->kodept,
                'location_program' => $dpl->location_program,
            ]);
            User::factory()->role('mahasiswa')->withPassword($password)->create([
                'name' => $mahasiswa->nama,
                'email' => $mahasiswa->email,
                'location_program' => $dpl->location_program,
                'akses' => $isKetua ? 'pjdesa' : null,
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

            // Mahasiswa pertama di tiap desa jadi ketua kelompok
            if ($isKetua) {
                Pjdesa::create(['email' => $mahasiswa->email, 'id_desa' => $desa->id_desa]);
            }
        }
    }
}
