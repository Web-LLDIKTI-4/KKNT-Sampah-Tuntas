<?php

namespace Database\Seeders;

use App\Models\PenguranganSampah;
use App\Services\PetaSebaranService;
use Database\Seeders\Concerns\SeedsDummyData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use RuntimeException;

/**
 * Data tambahan peta sebaran di kelurahan WilayahSeeder (1 PT = 1 kelurahan, mahasiswa tahun ini & tahun lalu).
 * Persen pengurangan peta berasal dari PendataanPemilahanSeeder (bulan terakhir per desa); seeder ini tidak mengisi pendataan.
 * Jalankan: php artisan db:seed --class=PetaSebaranDemoSeeder
 * Hapus:    php artisan db:seed --class=PetaSebaranDemoCleanupSeeder
 * Catatan: cleanup juga menghapus desa/kecamatan "(Demo)" warisan versi lama (ID UUID5 dari nama di LEGACY_WILAYAH).
 */
class PetaSebaranDemoSeeder extends Seeder
{
    use SeedsDummyData;

    // Hanya untuk mengenali wilayah warisan versi lama saat cleanup; tidak dibuat lagi
    public const SUFFIX = ' (Demo)';

    public const NIM_PREFIX = '99';

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

    // kelurahan (WilayahSeeder) => [jumlah tahun ini, jumlah tahun lalu]
    // Ditambahkan ke mahasiswa MahasiswaSeeder; kelurahan ke-i berpasangan dengan PT ke-i
    protected const DATA = [
        'Dago' => [18, 6],
        'Lebakgede' => [0, 3],
        'Pasteur' => [4, 0],
        'Cipedes' => [2, 5],
        'Citeureup' => [25, 12],
        'Cangkuang Wetan' => [8, 22],
        'Andir' => [12, 6],
        'Manggahang' => [1, 0],
        'Baros' => [15, 31],
        'Setiamanah' => [0, 1],
        'Cibabat' => [9, 19],
        'Pasirkaliki' => [3, 2],
    ];

    // Wilayah "(Demo)" versi lama: kecamatan => [desa]
    protected const LEGACY_WILAYAH = [
        'Baleendah' => ['Andir', 'Baleendah', 'Manggahang', 'Jelekong', 'Malakasari'],
        'Soreang' => ['Sadu', 'Soreang', 'Pamekaran', 'Cingcin'],
        'Dayeuhkolot' => ['Citeureup', 'Cangkuang Wetan', 'Pasawahan', 'Sukapura'],
        'Bojongsoang' => ['Bojongsoang', 'Lengkong', 'Tegalluar', 'Buahbatu', 'Cipagalo'],
    ];

    public function run(): void
    {
        self::guard();
        // Kelurahan asli (berkoordinat) wajib ada; idempotent
        $this->callSilent(WilayahSeeder::class);

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
        foreach (self::LEGACY_WILAYAH as $namaKecamatan => $desaList) {
            $kecamatan[] = self::kecamatanId($namaKecamatan);
            foreach ($desaList as $namaDesa) {
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
        $nama = array_map(fn (string $n) => $n.self::SUFFIX, array_keys(self::LEGACY_WILAYAH));

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
            if (in_array($desa, self::LEGACY_WILAYAH[$kecamatan] ?? [], true)) {
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

        // Pendataan warisan versi lama tidak punya marker -> kenali lewat email mahasiswa demo, ambil sebelum mahasiswa dihapus
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
            $warn('Desa demo lama dilewati karena masih dirujuk data asli: '.implode(', ', $referencedDesa));
        }
        DB::table('desa')->whereIn('id_desa', array_diff($ids['desa'], $referencedDesa))->delete();

        $usedKecamatan = DB::table('desa')->whereIn('id_kecamatan', $ids['kecamatan'])->distinct()->pluck('id_kecamatan')->all();
        if ($usedKecamatan && $warn) {
            $warn('Kecamatan demo lama dilewati karena masih memiliki desa: '.implode(', ', $usedKecamatan));
        }
        DB::table('kecamatan')->whereIn('id_kecamatan', array_diff($ids['kecamatan'], $usedKecamatan))->delete();
    }

    protected function warn(string $message): void
    {
        $this->command?->warn($message);
    }

    protected function insert(): void
    {
        $now = now();
        $tahun = (int) date('Y');
        $mahasiswaRows = [];
        $lokasiRows = [];
        $seq = 0;

        $kodeptList = DB::table('ref_satuanpendidikan')->orderBy('npsn')->pluck('npsn')->all();
        if (! $kodeptList) {
            $this->warn('ref_satuanpendidikan kosong: data diisi tanpa kodept, filter PT tidak bisa didemokan');
        }

        foreach ($this->penempatan() as $desaIndex => [$lokasi, $desa]) {
            if ($kodeptList && $desaIndex >= count($kodeptList)) {
                break;
            }
            $kodept = $kodeptList[$desaIndex] ?? null;
            $idDesa = $desa->id_desa;
            [$jumlahIni, $jumlahLalu] = self::DATA[$desa->desa];

            foreach ([$tahun => $jumlahIni, $tahun - 1 => $jumlahLalu] as $tahunLokasi => $jumlah) {
                for ($i = 0; $i < $jumlah; $i++) {
                    $seq++;
                    $idMahasiswa = (string) Str::uuid();
                    $mahasiswaRows[] = [
                        'id_mahasiswa' => $idMahasiswa,
                        'nim' => sprintf('%s%d%04d', self::NIM_PREFIX, $tahunLokasi, $seq),
                        'tahun_masuk' => $tahunLokasi - 3,
                        'nama' => fake()->name(),
                        'email' => 'peserta.'.$seq.'@example.test',
                        'prodi' => self::PRODI[$seq % count(self::PRODI)],
                        'kodept' => $kodept,
                        'location_program' => $lokasi->id,
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
        }

        foreach (array_chunk($mahasiswaRows, 200) as $chunk) {
            DB::table('mahasiswa')->insert($chunk);
        }
        foreach (array_chunk($lokasiRows, 200) as $chunk) {
            DB::table('mahasiswa_lokasi')->insert($chunk);
        }
    }
}
