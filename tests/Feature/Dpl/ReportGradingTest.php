<?php

namespace Tests\Feature\Dpl;

use App\Exports\LogHarianByMhsExport;
use App\Models\Dplmentoring;
use App\Models\Kpi;
use App\Models\Logbulanan;
use App\Models\Logkegiatan;
use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
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

    public function test_pt_cannot_search_or_order_by_hidden_deskripsi(): void
    {
        $pt = $this->loginAs('pt', ['email' => '041999']);
        $mhs = Mahasiswa::factory()->create(['kodept' => $pt->email]);
        Logkegiatan::factory()->create(['email' => $mhs->email, 'deskripsi' => 'aaa rahasia', 'tautan' => 'https://z.test']);
        Logkegiatan::factory()->create(['email' => $mhs->email, 'deskripsi' => 'zzz rahasia', 'tautan' => 'https://a.test']);

        $base = "admlogkegiatan/listdataserver/{$mhs->email}?draw=1&start=0&length=10"
            .'&columns[0][data]=deskripsi&columns[0][name]=deskripsi&columns[0][searchable]=true&columns[0][orderable]=true';

        $this->getJson($base.'&search[value]=rahasia', $this->ajax)->assertOk()->assertJsonPath('recordsFiltered', 0);
        $this->getJson($base.'&order[0][column]=0&order[0][dir]=asc', $this->ajax)
            ->assertOk()->assertJsonPath('data.0.tautan', 'https://a.test');
    }

    public function test_pt_cannot_use_raw_column_name_as_deskripsi_oracle(): void
    {
        $pt = $this->loginAs('pt', ['email' => '041998']);
        $mhs = Mahasiswa::factory()->create(['kodept' => $pt->email]);
        Logkegiatan::factory()->create(['email' => $mhs->email, 'deskripsi' => 'aaa rahasia']);
        Logkegiatan::factory()->create(['email' => $mhs->email, 'deskripsi' => 'zzz biasa']);

        $base = "admlogkegiatan/listdataserver/{$mhs->email}?draw=1&start=0&length=10"
            .'&columns[0][data]=x&columns[0][name]=logkegiatan.deskripsi&columns[0][searchable]=true&columns[0][orderable]=true';

        $hit = $this->getJson($base.'&search[value]=rahasia', $this->ajax)->assertOk();
        $miss = $this->getJson($base.'&search[value]=tidakadasamasekali', $this->ajax)->assertOk();
        $this->assertSame($miss->json('recordsFiltered'), $hit->json('recordsFiltered'));
        $this->assertStringNotContainsString('rahasia', json_encode($hit->json('data')));
    }

    public function test_pt_export_does_not_contain_deskripsi(): void
    {
        Excel::fake();
        Excel::matchByRegex();
        $pt = $this->loginAs('pt', ['email' => '041997']);
        Satuanpendidikan::factory()->create(['npsn' => $pt->email]);
        $mhs = Mahasiswa::factory()->create(['kodept' => $pt->email]);
        Logkegiatan::factory()->create(['email' => $mhs->email, 'deskripsi' => 'isi rahasia mahasiswa', 'id_kpi' => Kpi::factory()->create()->id_kpi]);

        $this->get("admlogkegiatan/export/{$mhs->email}")->assertOk();

        Excel::assertDownloaded('/^logharian_mahasiswa_.+\.xlsx$/', function (LogHarianByMhsExport $export) {
            $rows = $export->collection();

            return $rows->count() === 1
                && $rows->first()['Deskripsi'] === 'tidak ditampilkan'
                && ! str_contains(json_encode($rows), 'rahasia');
        });
    }

    public function test_export_handles_log_without_kpi_and_student_without_pt(): void
    {
        Excel::fake();
        Excel::matchByRegex();
        $this->loginAs('admin');
        $mhs = Mahasiswa::factory()->create(['kodept' => '049999']);
        Logkegiatan::factory()->create(['email' => $mhs->email, 'id_kpi' => null]);

        $this->get("admlogkegiatan/export/{$mhs->email}")->assertOk();

        Excel::assertDownloaded('/^logharian_mahasiswa_.+\.xlsx$/', function (LogHarianByMhsExport $export) {
            $row = $export->collection()->first();

            return $row['KPI'] === '-' && $row['Perguruan Tinggi'] === '-';
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
