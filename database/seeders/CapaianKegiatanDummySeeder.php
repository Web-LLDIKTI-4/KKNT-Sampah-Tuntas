<?php

namespace Database\Seeders;

use App\Models\CapaianKegiatan;
use App\Models\Desa;
use App\Models\KategoriKegiatan;
use App\Models\Kecamatan;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use Illuminate\Database\Seeder;

/**
 * Data dummy capaian kegiatan: master kategori kegiatan (bila kosong), ketua kelompok dummy (@kknt.test) untuk PT yang belum punya ketua
 * (1 ketua di 1 kelurahan per PT),
 * dan isian capaian campuran (selesai / proses / belum ditindaklanjuti / belum mengisi).
 * Jalankan: php artisan db:seed --class=CapaianKegiatanDummySeeder
 */
class CapaianKegiatanDummySeeder extends Seeder
{
    private const DOMAIN = '@kknt.test';

    private const KATEGORI = ['Pengurangan Sampah Rumah Tangga', 'Bank Sampah', 'Pengolahan Sampah Organik'];

    private const JUMLAH_PT = 3;

    public function run(): void
    {
        $kategoriList = $this->kategoriList();
        $this->ketuaDummy();

        // Hanya ketua dummy yang belum punya isian, data ketua asli tidak disentuh
        $ketua = Pjdesa::where('email', 'like', '%'.self::DOMAIN)
            ->whereNotIn('email', CapaianKegiatan::select('email')->whereNotNull('email'))
            ->get();

        foreach ($ketua as $pj) {
            // UNIQUE(email, bulan): tiap kategori di bulan berbeda, mundur dari bulan ini
            foreach ($kategoriList->values() as $mundur => $kategori) {
                $this->isiCapaian($pj, $kategori, $mundur);
            }
        }

        $this->command?->info('Capaian kegiatan dummy: '.$kategoriList->count().' kategori, '.$ketua->count().' ketua kelompok diisi.');
    }

    private function kategoriList()
    {
        if (KategoriKegiatan::exists()) {
            return KategoriKegiatan::all();
        }

        return collect(self::KATEGORI)->map(fn ($nama) => KategoriKegiatan::create(['nama_kategori' => $nama]));
    }

    private function ketuaDummy(): void
    {
        $lokasi = LokasiProgram::orderBy('nama_lokasi')->get();
        if ($lokasi->isEmpty()) {
            $lokasi = collect([LokasiProgram::create(['nama_lokasi' => 'Kota Bandung'])]);
        }

        $pts = Satuanpendidikan::orderBy('nm_lemb')->limit(self::JUMLAH_PT)->get();
        while ($pts->count() < self::JUMLAH_PT) {
            $pts->push(Satuanpendidikan::factory()->create());
        }

        // 1 PT = 1 kelurahan: PT yang sudah punya ketua tidak ditambah; PT baru mendapat 1 ketua di kelurahan baru
        $kecamatan = null;

        foreach ($pts->values() as $i => $pt) {
            if (Pjdesa::whereIn('email', Mahasiswa::where('kodept', $pt->npsn)->select('email'))->exists()) {
                continue;
            }

            $email = 'ketua.pt'.($i + 1).self::DOMAIN;
            $mhs = Mahasiswa::factory()->create([
                'email' => $email,
                'kodept' => $pt->npsn,
                'location_program' => $lokasi[$i % $lokasi->count()]->id,
            ]);
            $kecamatan ??= Kecamatan::factory()->create();
            $desa = Desa::factory()->create(['id_kecamatan' => $kecamatan->id_kecamatan]);

            Mahasiswa_lokasi::create([
                'tahun' => (int) date('Y'),
                'id_mahasiswa' => $mhs->id_mahasiswa,
                'id_desa' => $desa->id_desa,
                'user_in_up' => $email,
            ]);
            Pjdesa::create(['email' => $email, 'id_desa' => $desa->id_desa]);
        }
    }

    private function isiCapaian(Pjdesa $pj, KategoriKegiatan $kategori, int $mundur): void
    {
        $acak = random_int(1, 100);
        if ($acak > 85) {
            return; // belum mengisi
        }

        CapaianKegiatan::factory()->create([
            'id_kategori' => $kategori->id_kategori,
            'id_pjdesa' => $pj->id_pjdesa,
            'email' => $pj->email,
            'bulan' => now()->startOfMonth()->subMonths($mundur)->toDateString(),
            'status_capaian' => $acak <= 60 ? 'Y' : ($acak <= 75 ? 'P' : 'N'),
        ]);
    }
}
