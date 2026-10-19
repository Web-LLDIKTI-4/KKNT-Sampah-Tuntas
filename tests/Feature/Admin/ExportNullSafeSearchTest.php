<?php

namespace Tests\Feature\Admin;

use App\Models\Dpl;
use App\Models\Dpllaporan;
use App\Models\Freeform;
use App\Models\KategoriKegiatan;
use App\Models\Logkegiatan;
use App\Models\Mahasiswa;
use App\Models\Nilaikonversi;
use App\Models\PendataanPemilahanSampah;
use App\Models\Satuanpendidikan;
use App\Models\Tugasakhir;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\Concerns\ReadsXlsx;
use Tests\TestCase;

// Export pola B: null-safe (mahasiswa/sp/PT tidak ada) dan mengikuti search/order tabel
class ExportNullSafeSearchTest extends TestCase
{
    use ReadsXlsx;
    use RefreshDatabase;

    private Mahasiswa $andi;

    private Mahasiswa $budi;

    protected function setUp(): void
    {
        parent::setUp();
        // Banyak export dalam satu test; throttle diuji di test lain
        $this->withoutMiddleware(ThrottleRequests::class);

        $sp = Satuanpendidikan::factory()->create(['nm_lemb' => 'Universitas Uji']);
        $this->andi = Mahasiswa::factory()->create(['nim' => '1001', 'nama' => 'Andi Pratama', 'kodept' => $sp->npsn]);
        // PT tidak ada di ref_satuanpendidikan
        $this->budi = Mahasiswa::factory()->create(['nim' => '1002', 'nama' => 'Budi_Santoso', 'kodept' => '99999999']);
    }

    private function dt(array $columns, array $extra = []): string
    {
        $cols = array_map(fn ($c) => ['data' => $c, 'name' => $c, 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '']], $columns);

        return '?'.http_build_query($extra + ['columns' => $cols]);
    }

    public function test_freeform_dan_structureform_null_safe(): void
    {
        $this->loginAs('admin');
        foreach ([$this->andi, $this->budi] as $mhs) {
            Freeform::factory()->create(['id_mahasiswa' => $mhs->id_mahasiswa]);
            Nilaikonversi::factory()->create(['id_mahasiswa' => $mhs->id_mahasiswa]);
        }
        // Mahasiswa sudah dihapus
        Freeform::factory()->create(['id_mahasiswa' => 999999]);
        Nilaikonversi::factory()->create(['id_mahasiswa' => 999999]);

        foreach (['admfreeform' => 'free_form_', 'admstructureform' => 'structure_form_'] as $uri => $prefix) {
            $ws = $this->xlsxFromResponse($this->get("$uri/export"), "/filename=$prefix.+\\.xlsx/");
            $rows = $this->dataRows($ws);
            $this->assertCount(3, $rows, $uri);
            $this->assertSame(['No.', 'NIM', 'Nama', 'Perguruan Tinggi', 'Prodi'], array_slice($this->headerRow($ws), 0, 5), $uri);
            $byNim = collect($rows)->keyBy(1);
            $this->assertSame('Universitas Uji', $byNim['1001'][3], $uri);
            $this->assertSame('-', $byNim['1002'][3], $uri);
            $this->assertSame(['-', '-', '-', '-'], array_slice($byNim['-'], 1, 4), $uri);
        }
    }

    public function test_laptugasakhir_null_safe(): void
    {
        Tugasakhir::factory()->create(['email' => $this->budi->email, 'nilai_dpl' => 70]);
        Tugasakhir::factory()->create(['email' => 'hilang@uji.test', 'nilai_dpl' => 60]);

        foreach (['admin' => 'laptugasakhir/export', 'kepala' => 'dpllaptugasakhir/export'] as $role => $uri) {
            $this->loginAs($role);
            $rows = collect($this->dataRows($this->xlsxFromResponse($this->get($uri))))->keyBy(1);
            $this->assertCount(2, $rows, $uri);
            $this->assertSame('-', $rows['1002'][3], $uri);
            $this->assertSame(['-', '-', '-'], array_slice($rows['-'], 1, 3), $uri);
        }
    }

    public function test_admlaporandpl_export_dpl_tanpa_pt_dan_tanpa_master(): void
    {
        $this->loginAs('admin');
        $dpl = Dpl::factory()->create(['nama' => 'Dosen Tanpa PT', 'kodept' => null]);
        Dpllaporan::factory()->create(['email' => $dpl->email, 'tahun' => 2026, 'bulan' => 9, 'deskripsi' => '<p>Monitoring <b>desa</b></p>']);
        Dpllaporan::factory()->create(['email' => 'yatim@uji.test', 'tahun' => 2026, 'bulan' => 8]);

        $ws = $this->xlsxFromResponse($this->get('admlaporandpl/export/'.rawurlencode($dpl->email)), '/filename=logbulanan_dpl_.+\.xlsx/');
        $this->assertSame(['No.', 'Nama DPL', 'NIDN', 'Perguruan Tinggi', 'Tahun', 'Bulan', 'Deskripsi'], $this->headerRow($ws));
        $rows = $this->dataRows($ws);
        $this->assertCount(1, $rows);
        $this->assertSame('Dosen Tanpa PT', $rows[0][1]);
        $this->assertSame('-', $rows[0][3]);
        $this->assertSame('September', $rows[0][5]);
        $this->assertSame('Monitoring desa', $rows[0][6]);

        // Email tanpa master DPL → 200, identitas "-"
        $rows = $this->dataRows($this->xlsxFromResponse($this->get('admlaporandpl/export/yatim@uji.test')));
        $this->assertCount(1, $rows);
        $this->assertSame(['-', '-', '-'], array_slice($rows[0], 1, 3));
    }

    public function test_freeform_dan_structureform_export_mengikuti_search_dan_order(): void
    {
        $this->loginAs('admin');
        foreach ([$this->andi, $this->budi] as $mhs) {
            Freeform::factory()->create(['id_mahasiswa' => $mhs->id_mahasiswa]);
            Nilaikonversi::factory()->create(['id_mahasiswa' => $mhs->id_mahasiswa]);
        }
        $columns = ['nim', 'nama', 'nm_lemb', 'prodi'];

        foreach (['admfreeform', 'admstructureform'] as $uri) {
            $nims = fn (array $extra) => $this->dataColumn($this->xlsxFromResponse($this->get("$uri/export".$this->dt($columns, $extra))), 1);

            $this->assertSame(['1001'], $nims(['search' => ['value' => 'Andi']]), $uri);
            $this->assertSame(['1001'], $nims(['search' => ['value' => 'Universitas Uji']]), $uri);
            $this->assertSame([], $nims(['search' => ['value' => 'zzqq']]), $uri);
            // "_" literal, bukan wildcard
            $this->assertSame(['1002'], $nims(['search' => ['value' => 'Budi_']]), $uri);
            $this->assertSame([], $nims(['search' => ['value' => 'Andi_']]), $uri);
            $this->assertSame(['1002', '1001'], $nims(['order' => [['column' => 1, 'dir' => 'desc']]]), $uri);

            // Kata kunci tampil di baris info
            $ws = $this->xlsxFromResponse($this->get("$uri/export".$this->dt($columns, ['search' => ['value' => 'Andi']])));
            $this->assertStringContainsString('Andi', $this->cellText($ws, 'A2'), $uri);
        }
    }

    public function test_export_per_mahasiswa_mengikuti_search(): void
    {
        $this->loginAs('admin');
        $sampah = KategoriKegiatan::factory()->create(['nama_kategori' => 'Pengelolaan Sampah']);
        $edukasi = KategoriKegiatan::factory()->create(['nama_kategori' => 'Edukasi']);
        Logkegiatan::factory()->create(['email' => $this->andi->email, 'tanggal' => '2026-09-01', 'id_kategori' => $sampah->id_kategori, 'deskripsi' => 'Pilah sampah']);
        Logkegiatan::factory()->create(['email' => $this->andi->email, 'tanggal' => '2026-09-02', 'id_kategori' => $edukasi->id_kategori, 'deskripsi' => 'Sosialisasi warga']);
        PendataanPemilahanSampah::factory()->create(['email' => $this->andi->email, 'tanggal' => '2026-05-12', 'nama_kepala_keluarga' => 'Pak Joko']);
        PendataanPemilahanSampah::factory()->create(['email' => $this->andi->email, 'tanggal' => '2026-05-13', 'nama_kepala_keluarga' => 'Bu Sri']);
        $email = rawurlencode($this->andi->email);

        $base = "admlogkegiatan/export/$email";
        $columns = ['tanggal', 'nama_kategori', 'deskripsi'];
        $this->assertCount(2, $this->dataRows($this->xlsxFromResponse($this->get($base))));
        $rows = $this->dataRows($this->xlsxFromResponse($this->get($base.$this->dt($columns, ['search' => ['value' => 'Edukasi']]))));
        $this->assertSame([['Edukasi', 'Sosialisasi warga']], array_map(fn ($r) => [$r[2], $r[4]], $rows));
        $this->assertSame([], $this->dataRows($this->xlsxFromResponse($this->get($base.$this->dt($columns, ['search' => ['value' => 'zzqq']])))));

        $base = "pendataanpemilahan/export/$email";
        $columns = ['tanggal', 'nama_kepala_keluarga', 'alamat_rumah'];
        $this->assertCount(2, $this->dataRows($this->xlsxFromResponse($this->get($base))));
        $this->assertSame(['Bu Sri'], $this->dataColumn($this->xlsxFromResponse($this->get($base.$this->dt($columns, ['search' => ['value' => 'Sri']]))), 2));
    }

    public function test_non_admin_tidak_bisa_export_admin(): void
    {
        $this->loginAs('pt');
        foreach (['admfreeform/export', 'admstructureform/export', 'laptugasakhir/export', 'admlaporandpl/export/x@uji.test'] as $uri) {
            $res = $this->get($uri);
            $this->assertTrue($res->isRedirect(), $uri);
        }

        auth()->logout();
        $this->get('admfreeform/export')->assertRedirect(route('login'));
    }
}
