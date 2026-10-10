<?php

namespace Database\Seeders\Concerns;

use App\Models\Desa;
use App\Models\KategoriKegiatan;
use App\Models\Kecamatan;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Konstanta & helper bersama seeder data simulasi (@kknt.test).
 * Seeder tidak saling oper variabel: prasyarat diambil dari DB lewat kunci tetap (email domain, NPSN, nama wilayah).
 */
trait SeedsDummyData
{
    protected const DOMAIN = '@kknt.test';

    protected const KATEGORI = 'Pengurangan Sampah Rumah Tangga';

    protected const KELOMPOK_PER_PT = 3;

    protected const ANGGOTA_PER_KELOMPOK = 4;

    // Lokasi => [kecamatan => [kelurahan]]; urutan menentukan pasangan PT <-> kelurahan
    protected const WILAYAH = [
        'Kota Bandung' => ['Coblong' => ['Dago', 'Lebakgede'], 'Sukajadi' => ['Pasteur', 'Cipedes']],
        'Kabupaten Bandung' => ['Dayeuhkolot' => ['Citeureup', 'Cangkuang Wetan'], 'Baleendah' => ['Andir', 'Manggahang']],
        'Kota Cimahi' => ['Cimahi Tengah' => ['Baros', 'Setiamanah'], 'Cimahi Utara' => ['Cibabat', 'Pasirkaliki']],
    ];

    // Jumlah PT = jumlah kelurahan agar 1 PT = 1 kelurahan; NPSN = 041001 dst sesuai urutan
    protected const PERGURUAN_TINGGI = [
        'Universitas Padjadjaran Simulasi',
        'Institut Teknologi Simulasi',
        'Universitas Pasundan Simulasi',
        'Universitas Islam Bandung Simulasi',
        'Politeknik Negeri Simulasi',
        'Universitas Telkom Simulasi',
        'Universitas Katolik Parahyangan Simulasi',
        'Universitas Komputer Indonesia Simulasi',
        'Universitas Jenderal Achmad Yani Simulasi',
        'Institut Teknologi Nasional Simulasi',
        'Universitas Widyatama Simulasi',
        'Universitas Langlangbuana Simulasi',
    ];

    private ?string $passwordHash = null;

    private array $akun = [];

    protected function seedRandom(): void
    {
        mt_srand(2026);
        fake()->seed(2026);
        // Riwayat unique() direset agar hasil sama saat dijalankan ulang di proses yang sama
        fake()->unique(true);
    }

    protected function password(): string
    {
        return config('app.seed_password') ?: 'password';
    }

    protected static function npsn(int $index): string
    {
        return sprintf('041%03d', $index + 1);
    }

    protected static function slugLokasi(LokasiProgram $lokasi): string
    {
        return Str::slug($lokasi->nama_lokasi, '');
    }

    protected function requireData(bool $ada, string $seeder): void
    {
        if (! $ada) {
            throw new RuntimeException("Jalankan {$seeder} dulu.");
        }
    }

    /**
     * Pasangan [LokasiProgram, Desa] urut lokasi -> kecamatan -> kelurahan; index = index PT.
     *
     * @return array<int, array{0: LokasiProgram, 1: Desa}>
     */
    protected function penempatan(): array
    {
        $hasil = [];
        foreach (self::WILAYAH as $namaLokasi => $kecamatanList) {
            $lokasi = LokasiProgram::where('nama_lokasi', $namaLokasi)->first();
            $this->requireData($lokasi !== null, 'WilayahSeeder');

            foreach ($kecamatanList as $namaKecamatan => $desaList) {
                $kecamatan = Kecamatan::where('kecamatan', $namaKecamatan)->first();
                $this->requireData($kecamatan !== null, 'WilayahSeeder');

                foreach ($desaList as $namaDesa) {
                    $desa = Desa::where('id_kecamatan', $kecamatan->id_kecamatan)->where('desa', $namaDesa)->first();
                    $this->requireData($desa !== null, 'WilayahSeeder');
                    $hasil[] = [$lokasi, $desa];
                }
            }
        }

        return $hasil;
    }

    /**
     * @return Collection<int, Satuanpendidikan> index = index PT
     */
    protected function perguruanTinggi(): Collection
    {
        $npsn = collect(array_keys(self::PERGURUAN_TINGGI))->map(fn (int $i) => self::npsn($i));
        $pts = Satuanpendidikan::whereIn('npsn', $npsn)->orderBy('npsn')->get()->values();
        $this->requireData($pts->count() === count(self::PERGURUAN_TINGGI), 'PerguruanTinggiSeeder');

        return $pts;
    }

    protected function kategoriMaster(): KategoriKegiatan
    {
        $kategori = KategoriKegiatan::where('nama_kategori', self::KATEGORI)->first();
        $this->requireData($kategori !== null, 'KategoriKegiatanSeeder');

        return $kategori;
    }

    // Mahasiswa kelompok (punya DPL pembimbing), urut email agar deterministik
    protected function mahasiswaKelompok(): Collection
    {
        $mahasiswa = Mahasiswa::where('email', 'like', '%'.self::DOMAIN)
            ->whereIn('email', fn ($q) => $q->select('email_mahasiswa')->from('dpl_mentoring'))
            ->orderBy('email')
            ->get();
        $this->requireData($mahasiswa->isNotEmpty(), 'MahasiswaSeeder');

        return $mahasiswa;
    }

    protected function scopeDummy(Builder $query, string $kolom = 'email'): Builder
    {
        return $query->where($kolom, 'like', '%'.self::DOMAIN);
    }

    // Buat akun bila belum ada; akun yang sudah ada tidak diubah
    protected function user(string $role, string $email, string $nama, ?string $lokasi = null, ?string $akses = null, string $ket = '', bool $tampil = true): User
    {
        $user = User::where('email', $email)->first();
        if ($user) {
            $ket = 'Sudah ada, tidak diubah';
        } else {
            $this->passwordHash ??= Hash::make($this->password());
            $user = User::forceCreate([
                'name' => $nama,
                'email' => $email,
                'email_verified_at' => now(),
                'password' => $this->passwordHash,
                'role' => $role,
                'location_program' => $lokasi,
                'akses' => $akses,
            ]);
        }

        if ($tampil) {
            $this->akun[] = [$role, $email, $nama, $ket];
        }

        return $user;
    }

    protected function tampilkanAkun(): void
    {
        if ($this->akun) {
            $this->command?->table(['Role', 'Login', 'Nama', 'Keterangan'], $this->akun);
        }
    }

    // PDF 1 halaman valid berisi judul, tanpa dependency tambahan
    protected function pdfMini(string $judul): string
    {
        $teks = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], Str::ascii($judul));
        $stream = "BT /F1 16 Tf 50 780 Td ({$teks}) Tj ET";

        $objek = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offset = [];
        foreach ($objek as $n => $isi) {
            $offset[] = strlen($pdf);
            $pdf .= ($n + 1)." 0 obj\n{$isi}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objek) + 1)."\n0000000000 65535 f \n";
        foreach ($offset as $o) {
            $pdf .= sprintf("%010d 00000 n \n", $o);
        }

        return $pdf."trailer\n<< /Size ".(count($objek) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }
}
