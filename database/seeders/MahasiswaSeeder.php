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
use Database\Seeders\Concerns\SeedsDummyData;
use Illuminate\Database\Seeder;

/**
 * Per PT: 3 kelompok (1 ketua + 4 anggota) di kelurahan pasangan PT, pj_desa (ketua), mahasiswa_lokasi, dplmentoring, akun;
 * plus 2 mahasiswa belum pilih lokasi (PT 1) dan 2 mahasiswa sebaran per PT (tanpa DPL).
 * Jalankan: php artisan db:seed --class=MahasiswaSeeder
 * Prasyarat: WilayahSeeder, PerguruanTinggiSeeder, DplSeeder
 */
class MahasiswaSeeder extends Seeder
{
    use SeedsDummyData;

    public function run(): void
    {
        $pts = $this->perguruanTinggi();
        $penempatan = $this->penempatan();
        $dpls = $pts->map(function (Satuanpendidikan $pt, int $i) use ($penempatan) {
            $dpl = Dpl::where('email', 'dpl.pt'.($i + 1).'.'.self::slugLokasi($penempatan[$i][0]).self::DOMAIN)->first();
            $this->requireData($dpl !== null, 'DplSeeder');

            return $dpl;
        });
        $this->seedRandom();

        foreach ($pts as $i => $pt) {
            [$lokasi, $desa] = $penempatan[$i];
            foreach (range(1, self::KELOMPOK_PER_PT) as $k) {
                $this->kelompok($pt, $i + 1, $k, $lokasi, $desa, $dpls[$i]);
            }
        }

        $this->belumPilihLokasi($pts->first());
        $this->sebaranMahasiswa($pts, $penempatan);

        $this->akun[] = ['mahasiswa', '-', Mahasiswa::where('email', 'like', '%'.self::DOMAIN)->count().' mahasiswa', 'Anggota: mhs{m}.k{n}.pt{i}.<lokasi>'.self::DOMAIN];
        $this->tampilkanAkun();
    }

    private function kelompok(Satuanpendidikan $pt, int $noPt, int $noKelompok, LokasiProgram $lokasi, Desa $desa, Dpl $dpl): void
    {
        $suffix = '.k'.$noKelompok.'.pt'.$noPt.'.'.self::slugLokasi($lokasi).self::DOMAIN;
        $prefixes = array_merge(['ketua'], array_map(fn ($n) => 'mhs'.$n, range(1, self::ANGGOTA_PER_KELOMPOK)));

        foreach ($prefixes as $prefix) {
            $isKetua = $prefix === 'ketua';
            $mhs = $this->mahasiswa($prefix.$suffix, $pt, $lokasi, $desa);
            $this->user('mahasiswa', $mhs->email, $mhs->nama, $lokasi->id, $isKetua ? 'pjdesa' : null,
                ($isKetua ? 'Ketua kelompok' : 'Anggota').' '.$noKelompok.' – Kel. '.$desa->desa, tampil: $isKetua);

            Dplmentoring::firstOrCreate(['email_mahasiswa' => $mhs->email], ['email_dpl' => $dpl->email]);
            if ($isKetua) {
                Pjdesa::firstOrCreate(['email' => $mhs->email], ['id_desa' => $desa->id_desa]);
            }
        }
    }

    private function belumPilihLokasi(Satuanpendidikan $pt): void
    {
        foreach ([1, 2] as $n) {
            $mhs = $this->mahasiswa('belumlokasi'.$n.'.pt1'.self::DOMAIN, $pt);
            $this->user('mahasiswa', $mhs->email, $mhs->nama, ket: 'Belum memilih lokasi');
        }
    }

    // Mahasiswa tambahan tanpa DPL, tetap di kelurahan PT-nya (1 PT = 1 kelurahan)
    private function sebaranMahasiswa($pts, array $penempatan): void
    {
        foreach ($pts as $i => $pt) {
            [$lokasi, $desa] = $penempatan[$i];
            foreach ([1, 2] as $n) {
                $mhs = $this->mahasiswa('sebaran'.$n.'.pt'.($i + 1).'.'.self::slugLokasi($lokasi).self::DOMAIN, $pt, $lokasi, $desa);
                $this->user('mahasiswa', $mhs->email, $mhs->nama, $lokasi->id, tampil: false);
            }
        }
    }

    private function mahasiswa(string $email, Satuanpendidikan $pt, ?LokasiProgram $lokasi = null, ?Desa $desa = null): Mahasiswa
    {
        $mhs = Mahasiswa::where('email', $email)->first() ?? Mahasiswa::factory()->create([
            'email' => $email,
            'kodept' => $pt->npsn,
            'location_program' => $lokasi?->id,
        ]);

        if ($desa) {
            Mahasiswa_lokasi::firstOrCreate(
                ['tahun' => (int) date('Y'), 'id_mahasiswa' => $mhs->id_mahasiswa],
                ['id_desa' => $desa->id_desa, 'user_in_up' => $mhs->email],
            );
        }

        return $mhs;
    }
}
