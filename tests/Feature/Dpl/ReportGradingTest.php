<?php

namespace Tests\Feature\Dpl;

use App\Exports\LogHarianByMhsExport;
use App\Models\Dplmentoring;
use App\Models\Kpi;
use App\Models\Logbulanan;
use App\Models\Logkegiatan;
use App\Models\Mahasiswa;
use App\Models\Tugasakhir;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ReportGradingTest extends TestCase
{
    use RefreshDatabase;

    private array $ajax = ['X-Requested-With' => 'XMLHttpRequest'];

    private function bimbingan($dpl): Mahasiswa
    {
        $mhs = Mahasiswa::factory()->create();
        Dplmentoring::create(['email_mahasiswa' => $mhs->email, 'email_dpl' => $dpl->email]);

        return $mhs;
    }

    public function test_dpl_group_list_contains_only_mentees_with_counts(): void
    {
        $dpl = $this->loginAs('dpl');
        $mhs = $this->bimbingan($dpl);
        Logkegiatan::factory()->count(3)->create(['email' => $mhs->email]);
        Mahasiswa::factory()->create();

        $this->getJson('admlogkegiatan/listdatagrouping?draw=1&start=0&length=10', $this->ajax)
            ->assertOk()->assertJsonPath('recordsTotal', 1)->assertJsonPath('data.0.count_log', 3);
    }

    public function test_dpl_cannot_read_or_export_other_students_logs_by_email(): void
    {
        Excel::fake();
        $this->loginAs('dpl');
        $lain = Mahasiswa::factory()->create();

        foreach (['admlogkegiatan', 'admlogbulanan', 'admlogkehadiran'] as $prefix) {
            $this->getJson("$prefix/listdataserver/{$lain->email}?draw=1&start=0&length=10", $this->ajax)->assertNotFound();
            $this->get("$prefix/export/{$lain->email}")->assertNotFound();
        }
    }

    public function test_admin_can_read_any_student_logs(): void
    {
        $this->loginAs('admin');
        $mhs = Mahasiswa::factory()->create();
        Logkegiatan::factory()->create(['email' => $mhs->email]);

        $this->getJson("admlogkegiatan/listdataserver/{$mhs->email}?draw=1&start=0&length=10", $this->ajax)
            ->assertOk()->assertJsonPath('recordsTotal', 1);
    }

    public function test_student_log_detail_and_export_use_daily_log_columns(): void
    {
        Excel::fake();
        $this->loginAs('admin');
        $mhs = Mahasiswa::factory()->create();
        $kpi = Kpi::factory()->create(['nama_kpi' => 'Pengelolaan Sampah']);
        $log = Logkegiatan::factory()->create([
            'email' => $mhs->email,
            'tanggal' => '2026-10-01',
            'deskripsi' => '<p>Membersihkan lingkungan</p>',
            'volume' => '2',
            'satuan' => 'kegiatan',
            'id_kpi' => $kpi->id_kpi,
            'tautan' => 'https://example.test/bukti',
        ]);

        $row = $this->getJson("admlogkegiatan/listdataserver/{$mhs->email}?draw=1&start=0&length=10", $this->ajax)
            ->assertOk()
            ->json('data.0');

        $this->assertSame('Membersihkan lingkungan', strip_tags($row['deskripsi']));
        $this->assertSame('2', (string) $row['volume']);
        $this->assertSame('kegiatan', $row['satuan']);
        $this->assertSame('Pengelolaan Sampah', $row['nama_kpi']);
        $this->assertSame('<a href="https://example.test/bukti" target="_blank" rel="noopener noreferrer">Lihat bukti</a>', $row['tautan']);

        $this->get("admlogkegiatan/export/{$mhs->email}")->assertOk();
        Excel::matchByRegex();
        Excel::assertDownloaded('/^logharian_mahasiswa_.+\.xlsx$/', function (LogHarianByMhsExport $export) {
            $this->assertSame([
                'No', 'Tanggal', 'Deskripsi Kegiatan', 'Volume/Kuantitas Output', 'Satuan', 'Aktivitas', 'Tautan Bukti',
            ], $export->headings());
            $this->assertSame([
                1, '01-10-2026', 'Membersihkan lingkungan', '2', 'kegiatan',
                'Pengelolaan Sampah', 'https://example.test/bukti',
            ], $export->collection()->first());

            return true;
        });
    }

    public function test_only_mentor_dpl_can_grade_log_bulanan(): void
    {
        $dpl = $this->loginAs('dpl');
        $milik = Logbulanan::factory()->create(['email' => $this->bimbingan($dpl)->email, 'nilai' => null]);
        $lain = Logbulanan::factory()->create(['email' => Mahasiswa::factory()->create()->email, 'nilai' => null]);

        $this->put('admlogbulanan/updatenilai', ['id_logbulanan' => $lain->id_logbulanan, 'nilai' => '80'])->assertForbidden();
        $this->put('admlogbulanan/updatenilai', ['id_logbulanan' => $milik->id_logbulanan, 'nilai' => '85'])
            ->assertJsonValidationErrors('nilai', 'errors');
        $this->put('admlogbulanan/updatenilai', ['id_logbulanan' => $milik->id_logbulanan, 'nilai' => '80', 'hasil_verifikasi' => 'Baik'])
            ->assertJson(['success' => true]);

        $this->assertSame('80', $milik->fresh()->nilai);
        $this->assertNull($lain->fresh()->nilai);
    }

    public function test_only_mentor_dpl_can_grade_tugas_akhir(): void
    {
        $dpl = $this->loginAs('dpl');
        $milik = Tugasakhir::factory()->create(['email' => $this->bimbingan($dpl)->email]);
        $lain = Tugasakhir::factory()->create(['email' => Mahasiswa::factory()->create()->email]);

        $this->put('dpllaptugasakhir/nilai', ['id_tugasakhir' => $lain->id_tugasakhir, 'nilai_dpl' => 90])->assertNotFound();
        $this->put('dpllaptugasakhir/nilai', ['id_tugasakhir' => $milik->id_tugasakhir, 'nilai_dpl' => 150])
            ->assertJsonValidationErrors('nilai_dpl', 'errors');
        $this->put('dpllaptugasakhir/nilai', ['id_tugasakhir' => $milik->id_tugasakhir, 'nilai_dpl' => 90])
            ->assertJson(['success' => true]);

        $this->assertSame(90, $milik->fresh()->nilai_dpl);
        $this->assertNull($lain->fresh()->nilai_dpl);
    }

    public function test_dpl_laporan_uses_shared_monthly_rules(): void
    {
        $this->loginAs('dpl');

        $this->put('dpllaporan/insert', ['bulan' => 3, 'tahun' => date('Y'), 'deskripsi' => 'terlalu pendek'])
            ->assertJsonPath('errors.deskripsi.0', 'Deskripsi minimal 200 kata!');
        $this->put('dpllaporan/insert', ['bulan' => 3, 'tahun' => date('Y'), 'deskripsi' => str_repeat('kata ', 200)])
            ->assertJson(['success' => true]);
    }
}
