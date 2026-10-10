<?php

namespace Database\Seeders;

use App\Models\PenguranganSampah;
use App\Services\PetaSebaranService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use RuntimeException;

/**
 * Data demo peta sebaran (maks 18 desa, 1 PT = 1 desa, mahasiswa tahun ini & tahun lalu, pendataan pemilahan sampah).
 * Tidak terdaftar di DatabaseSeeder.
 * Jalankan: php artisan db:seed --class=PetaSebaranDemoSeeder
 * Hapus:    php artisan db:seed --class=PetaSebaranDemoCleanupSeeder
 * Catatan: ID desa/kecamatan = UUID5 dari nama; mengubah nama di WILAYAH membuat cleanup tidak mengenali data lama.
 */
class PetaSebaranDemoSeeder extends Seeder
{
    public const SUFFIX = ' (Demo)';

    public const NIM_PREFIX = 'DEMO';

    public const MARKER = 'seeder-demo';

    // Namespace tetap untuk UUID5 data demo; jangan diubah
    protected const UUID_NAMESPACE = '6f1c2b8e-4a3d-5e7f-9b10-2c4d6e8fa0b1';

    // Tabel yang merujuk desa; desa demo yang masih dirujuk data asli tidak dihapus
    protected const DESA_REFERENCES = ['mahasiswa_lokasi', 'desa_profile', 'pj_desa', 'pengurangan_sampah'];

    protected const PRODI = [
        'Teknik Lingkungan',
        'Kesehatan Masyarakat',
        'Agroteknologi',
        'Ilmu Komunikasi',
        'Teknik Sipil',
    ];

    // kecamatan => [desa => [lat, lng, jumlah tahun ini, jumlah tahun lalu, persen pengurangan|null, hanya bulan lama]]
    // Urutan menentukan pasangan desa ke-i <-> PT ke-i; 12 desa pertama mencakup 0, 1, 2 dan tier 1-5
    protected const WILAYAH = [
        'Baleendah' => [
            'Andir' => [-6.9951, 107.6312, 12, 6, 23.5, false],
            'Baleendah' => [-7.0040, 107.6260, 35, 22, 31, false],
            'Manggahang' => [-7.0130, 107.6370, 4, 0, null, false],
            'Jelekong' => [-7.0230, 107.6460, 0, 3, null, false],
            'Malakasari' => [-6.9990, 107.6150, 8, 14, 12, false],
        ],
        'Soreang' => [
            'Sadu' => [-7.0412, 107.5101, 1, 0, 5, false],
            'Soreang' => [-7.0330, 107.5180, 24, 31, 45, false],
            'Pamekaran' => [-7.0250, 107.5060, 2, 5, 18, true],
            'Cingcin' => [-7.0450, 107.5290, 0, 1, null, false],
        ],
        'Dayeuhkolot' => [
            'Citeureup' => [-6.9780, 107.6380, 42, 12, 27, false],
            'Cangkuang Wetan' => [-6.9840, 107.6200, 15, 8, null, false],
            'Pasawahan' => [-6.9900, 107.6260, 5, 2, 8.5, false],
            'Sukapura' => [-6.9740, 107.6290, 9, 19, 21, false],
        ],
        'Bojongsoang' => [
            'Bojongsoang' => [-6.9800, 107.6480, 28, 10, 38, false],
            'Lengkong' => [-6.9700, 107.6600, 18, 4, null, false],
            'Tegalluar' => [-6.9650, 107.6900, 2, 0, 15, false],
            'Buahbatu' => [-6.9750, 107.6750, 7, 26, 3, false],
            'Cipagalo' => [-6.9620, 107.6460, 3, 2, null, false],
        ],
    ];

    // Sampah terkelola per rumah per bulan (organik + anorganik = 2 kg); residu diturunkan dari persen
    protected const ORGANIK_KG = 1.2;

    protected const ANORGANIK_KG = 0.8;

    public function run(): void
    {
        self::guard();

        DB::transaction(function () {
            self::cleanup($this->warn(...));
            $this->insert();
        });
        self::flushCaches();
    }

    public static function flushCaches(): void
    {
        PetaSebaranService::flushCache();
        PenguranganSampah::forgetPublicCache();
    }

    public static function guard(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Seeder demo peta sebaran hanya boleh dijalankan di local/testing.');
        }
    }

    public static function kecamatanId(string $nama): string
    {
        return Uuid::uuid5(self::UUID_NAMESPACE, 'kecamatan:'.$nama)->toString();
    }

    public static function desaId(string $kecamatan, string $nama): string
    {
        // Nama desa bisa sama di kecamatan berbeda (mis. Baleendah) -> sertakan kecamatan
        return Uuid::uuid5(self::UUID_NAMESPACE, 'desa:'.$kecamatan.'/'.$nama)->toString();
    }

    /**
     * @return array{kecamatan: list<string>, desa: list<string>}
     */
    public static function demoIds(): array
    {
        $kecamatan = [];
        $desa = [];
        foreach (self::WILAYAH as $namaKecamatan => $desaList) {
            $kecamatan[] = self::kecamatanId($namaKecamatan);
            foreach (array_keys($desaList) as $namaDesa) {
                $desa[] = self::desaId($namaKecamatan, $namaDesa);
            }
        }

        return ['kecamatan' => $kecamatan, 'desa' => $desa];
    }

    /**
     * UUID5 demo ids plus existing rows matching demo natural keys (legacy rows may use other ids).
     *
     * @return array{kecamatan: list<string>, desa: list<string>}
     */
    public static function existingDemoIds(): array
    {
        $ids = self::demoIds();
        $kecamatan = self::existingKecamatanByNama();
        $desa = array_values(self::existingDesaByKey(array_values($kecamatan)));

        return [
            'kecamatan' => array_values(array_unique([...$ids['kecamatan'], ...array_values($kecamatan)])),
            'desa' => array_values(array_unique([...$ids['desa'], ...$desa])),
        ];
    }

    /**
     * @return array<string, string> nama kecamatan (tanpa suffix) => id_kecamatan
     */
    protected static function existingKecamatanByNama(): array
    {
        $nama = array_map(fn (string $n) => $n.self::SUFFIX, array_keys(self::WILAYAH));

        return DB::table('kecamatan')->whereIn('kecamatan', $nama)->orderByDesc('created_at')->get(['id_kecamatan', 'kecamatan'])
            ->mapWithKeys(fn ($row) => [Str::beforeLast($row->kecamatan, self::SUFFIX) => $row->id_kecamatan])
            ->all();
    }

    /**
     * @param  list<string>  $idKecamatan
     * @return array<string, string> "id_kecamatan|nama desa (tanpa suffix)" => id_desa
     */
    protected static function existingDesaByKey(array $idKecamatan): array
    {
        if (! $idKecamatan) {
            return [];
        }
        $namaKecamatan = DB::table('kecamatan')->whereIn('id_kecamatan', $idKecamatan)->pluck('kecamatan', 'id_kecamatan');
        $result = [];
        foreach (DB::table('desa')->whereIn('id_kecamatan', $idKecamatan)->where('desa', 'like', '%'.self::SUFFIX)->orderByDesc('created_at')->get(['id_desa', 'id_kecamatan', 'desa']) as $row) {
            $kecamatan = Str::beforeLast((string) $namaKecamatan[$row->id_kecamatan], self::SUFFIX);
            $desa = Str::beforeLast($row->desa, self::SUFFIX);
            if (isset(self::WILAYAH[$kecamatan][$desa])) {
                $result[$row->id_kecamatan.'|'.$desa] = $row->id_desa;
            }
        }

        return $result;
    }

    /**
     * @param  callable(string): void|null  $warn
     */
    public static function cleanup(?callable $warn = null): void
    {
        $ids = self::existingDemoIds();

        $demoMahasiswaIds = DB::table('mahasiswa_lokasi')
            ->where('user_in_up', self::MARKER)
            ->whereNotNull('id_mahasiswa')
            ->distinct()
            ->pluck('id_mahasiswa')
            ->all();

        // Pendataan tidak punya marker -> kenali lewat email mahasiswa demo, ambil sebelum mahasiswa dihapus
        foreach (array_chunk($demoMahasiswaIds, 500) as $chunk) {
            $emails = DB::table('mahasiswa')->whereIn('id_mahasiswa', $chunk)->whereNotNull('email')->pluck('email')->all();
            if ($emails) {
                DB::table('pendataan_pemilahan_sampah')->whereIn('email', $emails)->delete();
            }
        }

        DB::table('mahasiswa_lokasi')->where('user_in_up', self::MARKER)->delete();
        foreach (array_chunk($demoMahasiswaIds, 500) as $chunk) {
            DB::table('mahasiswa')->whereIn('id_mahasiswa', $chunk)->delete();
        }

        $referencedDesa = [];
        foreach (self::DESA_REFERENCES as $table) {
            $referencedDesa = array_merge(
                $referencedDesa,
                DB::table($table)->whereIn('id_desa', $ids['desa'])->distinct()->pluck('id_desa')->all()
            );
        }
        $referencedDesa = array_values(array_unique($referencedDesa));

        if ($referencedDesa && $warn) {
            $warn('Desa demo dilewati karena masih dirujuk data asli: '.implode(', ', $referencedDesa));
        }
        DB::table('desa')->whereIn('id_desa', array_diff($ids['desa'], $referencedDesa))->delete();

        $usedKecamatan = DB::table('desa')->whereIn('id_kecamatan', $ids['kecamatan'])->distinct()->pluck('id_kecamatan')->all();
        if ($usedKecamatan && $warn) {
            $warn('Kecamatan demo dilewati karena masih memiliki desa: '.implode(', ', $usedKecamatan));
        }
        DB::table('kecamatan')->whereIn('id_kecamatan', array_diff($ids['kecamatan'], $usedKecamatan))->delete();
    }

    protected function warn(string $message): void
    {
        $this->command?->warn($message);
    }

    /**
     * Bulan data sampah demo: 3 bulan terakhir sebelum bulan berjalan di tahun berjalan; Januari -> bulan berjalan.
     *
     * @return list<int>
     */
    protected static function bulanSampah(): array
    {
        $bulanIni = (int) date('n');

        return $bulanIni > 1 ? range(max(1, $bulanIni - 3), $bulanIni - 1) : [$bulanIni];
    }

    protected function insert(): void
    {
        $now = now();
        $tahun = (int) date('Y');
        $kecamatanRows = [];
        $desaRows = [];
        $mahasiswaRows = [];
        $lokasiRows = [];
        $pendataanRows = [];
        $seq = 0;
        $bulanSampah = self::bulanSampah();

        $kodeptList = DB::table('ref_satuanpendidikan')->orderBy('npsn')->pluck('npsn')->all();
        if (! $kodeptList) {
            $this->warn('ref_satuanpendidikan kosong: desa demo diisi tanpa kodept, filter PT tidak bisa didemokan');
        }
        $jumlahDesa = $kodeptList ? min(18, count($kodeptList)) : 18;

        // Reuse existing demo rows by natural key (nama) so reruns never duplicate wilayah
        $existingKecamatan = self::existingKecamatanByNama();
        $existingDesa = self::existingDesaByKey(array_values($existingKecamatan));

        $desaIndex = 0;
        foreach (self::WILAYAH as $namaKecamatan => $desaList) {
            if ($desaIndex >= $jumlahDesa) {
                break;
            }
            $idKecamatan = $existingKecamatan[$namaKecamatan] ?? self::kecamatanId($namaKecamatan);
            isset($existingKecamatan[$namaKecamatan]) || $kecamatanRows[] = [
                'id_kecamatan' => $idKecamatan,
                'kecamatan' => $namaKecamatan.self::SUFFIX,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            foreach ($desaList as $namaDesa => [$lat, $lng, $jumlahIni, $jumlahLalu, $persen, $hanyaBulanLama]) {
                if ($desaIndex >= $jumlahDesa) {
                    break;
                }
                $kodept = $kodeptList[$desaIndex] ?? null;
                $desaIndex++;

                $desaKey = $idKecamatan.'|'.$namaDesa;
                $idDesa = $existingDesa[$desaKey] ?? self::desaId($namaKecamatan, $namaDesa);
                isset($existingDesa[$desaKey]) || $desaRows[] = [
                    'id_desa' => $idDesa,
                    'id_kecamatan' => $idKecamatan,
                    'desa' => $namaDesa.self::SUFFIX,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $emailTahunIni = [];
                foreach ([$tahun => $jumlahIni, $tahun - 1 => $jumlahLalu] as $tahunLokasi => $jumlah) {
                    for ($i = 0; $i < $jumlah; $i++) {
                        $seq++;
                        $idMahasiswa = (string) Str::uuid();
                        $email = 'demo.'.$seq.'@example.test';
                        $tahunLokasi === $tahun && $emailTahunIni[] = $email;
                        $mahasiswaRows[] = [
                            'id_mahasiswa' => $idMahasiswa,
                            'nim' => sprintf('%s%d%04d', self::NIM_PREFIX, $tahunLokasi, $seq),
                            'tahun_masuk' => $tahunLokasi - 3,
                            'nama' => 'Mahasiswa Demo '.$seq,
                            'email' => $email,
                            'prodi' => self::PRODI[$seq % count(self::PRODI)],
                            'kodept' => $kodept,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                        $lokasiRows[] = [
                            'id_lokasi' => (string) Str::uuid(),
                            'tahun' => $tahunLokasi,
                            'id_mahasiswa' => $idMahasiswa,
                            'id_desa' => $idDesa,
                            'user_in_up' => self::MARKER,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if ($persen === null || ! $emailTahunIni) {
                    continue;
                }
                // residu = terkelola x (100/p - 1) -> persen pengurangan desa = p
                $residu = round((self::ORGANIK_KG + self::ANORGANIK_KG) * (100 / $persen - 1), 2);
                $jumlahRumah = 3 + ($desaIndex % 3);
                $bulanDesa = $hanyaBulanLama ? [$bulanSampah[0]] : $bulanSampah;
                foreach ($bulanDesa as $bulan) {
                    for ($r = 1; $r <= $jumlahRumah; $r++) {
                        $pendataanRows[] = [
                            'id_pendataan' => (string) Str::uuid(),
                            'email' => $emailTahunIni[($r - 1) % count($emailTahunIni)],
                            'tanggal' => sprintf('%d-%02d-%02d', $tahun, $bulan, 10 + $r),
                            'nama_kepala_keluarga' => 'KK Demo '.$r,
                            'alamat_rumah' => 'Jl. Demo No. '.$r.', '.$namaDesa,
                            'rt' => '001',
                            'rw' => '001',
                            'memilah' => $r % 4 !== 0,
                            'organik_kg' => self::ORGANIK_KG,
                            'anorganik_kg' => self::ANORGANIK_KG,
                            'residu_kg' => $residu,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }
        }

        if ($kecamatanRows) {
            DB::table('kecamatan')->insert($kecamatanRows);
        }
        if ($desaRows) {
            DB::table('desa')->insert($desaRows);
        }
        foreach (array_chunk($mahasiswaRows, 200) as $chunk) {
            DB::table('mahasiswa')->insert($chunk);
        }
        foreach (array_chunk($lokasiRows, 200) as $chunk) {
            DB::table('mahasiswa_lokasi')->insert($chunk);
        }
        foreach (array_chunk($pendataanRows, 200) as $chunk) {
            DB::table('pendataan_pemilahan_sampah')->insert($chunk);
        }
    }
}
