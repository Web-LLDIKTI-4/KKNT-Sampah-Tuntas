<?php

namespace Tests\Feature\Mahasiswa;

use App\Exports\PendataanPemilahanSampahByMhsExport;
use App\Models\Dplmentoring;
use App\Models\Logkegiatan;
use App\Models\Mahasiswa;
use App\Models\PendataanPemilahanSampah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class PendataanPemilahanSampahTest extends TestCase
{
    use RefreshDatabase;

    private array $ajax = ['X-Requested-With' => 'XMLHttpRequest'];

    private function payload(array $override = []): array
    {
        return $override + [
            'tanggal' => today()->toDateString(),
            'nama_kepala_keluarga' => 'Budi Santoso',
            'alamat_rumah' => 'Jl. Melati No. 5',
            'rt' => '003',
            'rw' => '07',
            'memilah' => '1',
            'organik_kg' => 2.5,
            'anorganik_kg' => 1.25,
            'residu_kg' => 0,
        ];
    }

    public function test_mahasiswa_can_add_edit_and_delete_population_waste_data(): void
    {
        $user = $this->loginAs('mahasiswa');
        $this->get('pendataanpemilahan')->assertOk()->assertSee('Pendataan Pemilahan Sampah Penduduk');

        $this->put('pendataanpemilahan/insert', $this->payload())->assertJson(['success' => true]);
        $data = PendataanPemilahanSampah::where('email', $user->email)->firstOrFail();
        $this->assertTrue(Str::isUuid($data->id_pendataan));
        $this->assertSame('Budi Santoso', $data->nama_kepala_keluarga);
        $this->assertSame(2.5, $data->organik_kg);

        $this->put('pendataanpemilahan/update', $this->payload([
            'id_pendataan' => $data->id_pendataan,
            'memilah' => '0',
            'rt' => '009',
        ]))->assertJson(['success' => true]);
        $this->assertFalse($data->fresh()->memilah);
        $this->assertSame('009', $data->fresh()->rt);

        $this->put('pendataanpemilahan/destroy', ['id_pendataan' => $data->id_pendataan])->assertJson(['success' => true]);
        $this->assertDatabaseMissing('pendataan_pemilahan_sampah', ['id_pendataan' => $data->id_pendataan]);
    }

    public function test_form_requires_all_survey_fields_and_rejects_invalid_input(): void
    {
        $this->loginAs('mahasiswa');

        $this->put('pendataanpemilahan/insert', [])
            ->assertJsonValidationErrors([
                'tanggal', 'nama_kepala_keluarga', 'alamat_rumah', 'rt', 'rw',
                'memilah', 'organik_kg', 'anorganik_kg', 'residu_kg',
            ], 'errors');
        $this->put('pendataanpemilahan/insert', $this->payload([
            'tanggal' => today()->addDay()->toDateString(),
            'rt' => '1/2',
            'memilah' => 'mungkin',
            'organik_kg' => -1,
        ]))->assertJsonValidationErrors(['tanggal', 'rt', 'memilah', 'organik_kg'], 'errors');
    }

    public function test_migration_copies_existing_household_data_out_of_daily_logs(): void
    {
        $migration = require database_path('migrations/2026_10_07_000001_create_pendataan_pemilahan_sampah_table.php');
        $migration->down();

        $dailyLog = Logkegiatan::create($this->payload() + ['email' => 'mahasiswa@pps.test']);
        $migration->up();

        $this->assertDatabaseHas('pendataan_pemilahan_sampah', [
            'id_pendataan' => $dailyLog->id_log,
            'email' => 'mahasiswa@pps.test',
            'nama_kepala_keluarga' => 'Budi Santoso',
            'organik_kg' => 2.5,
        ]);
        $this->assertNull($dailyLog->fresh()->deskripsi);
    }

    public function test_student_only_sees_and_changes_own_population_records(): void
    {
        $user = $this->loginAs('mahasiswa');
        PendataanPemilahanSampah::factory()->create(['email' => $user->email]);
        $other = PendataanPemilahanSampah::factory()->create(['email' => 'lain@pps.test']);

        $this->getJson('pendataanpemilahan/listdataserver?draw=1&start=0&length=10', $this->ajax)
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonStructure(['data' => [['action']]]);
        $this->get('pendataanpemilahan/edit/'.$other->id_pendataan)->assertNotFound();
        $this->put('pendataanpemilahan/update', $this->payload(['id_pendataan' => $other->id_pendataan]))->assertNotFound();
        $this->put('pendataanpemilahan/destroy', ['id_pendataan' => $other->id_pendataan])->assertNotFound();
    }

    public function test_all_roles_can_open_the_read_only_population_data_menu(): void
    {
        foreach (['admin', 'dpl', 'pt', 'kepala', 'pemda', 'mahasiswa'] as $role) {
            $this->loginAs($role);
            $this->get('pendataanpemilahan')->assertOk()
                ->assertSee('Pendataan Pemilahan Sampah Penduduk')
                ->assertSee(url('pendataanpemilahan'));
        }
    }

    public function test_non_students_are_view_only_with_view_action_in_group(): void
    {
        $this->loginAs('admin');
        $student = Mahasiswa::factory()->create(['email' => 'student@pps.test']);
        PendataanPemilahanSampah::factory()->count(3)->create(['email' => $student->email]);

        $this->get('pendataanpemilahan/listdata/'.rawurlencode($student->email))
            ->assertOk()
            ->assertSee('Nama Kepala Keluarga')
            ->assertDontSee('Aksi')
            ->assertDontSee('urlEdit');
        $this->getJson('pendataanpemilahan/listdataserver/'.rawurlencode($student->email).'?draw=1&start=0&length=10', $this->ajax)
            ->assertOk()
            ->assertJsonMissingPath('data.0.action');
        $grouped = $this->getJson('pendataanpemilahan/listdatagrouping?draw=1&start=0&length=10', $this->ajax)
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.count_log', 3);
        $action = (string) $grouped->json('data.0.action');
        $this->assertStringContainsString('/pendataanpemilahan/listdata/student%40pps.test', $action);
        $this->assertStringContainsString('Lihat Data', $action);
        $this->assertStringNotContainsString('Ubah Data', $action);
        $this->assertStringNotContainsString('btn-delete', $action);
        $this->assertStringNotContainsString('<a', (string) $grouped->json('data.0.nama_mahasiswa'));
        $this->get('pendataanpemilahan/listdatagroup')->assertOk()->assertSee('Aksi')->assertSee("data: 'action'", false);
        $this->get('pendataanpemilahan/tambah')->assertRedirect();
    }

    public function test_dpl_and_pt_grouped_counts_only_include_students_in_their_scope(): void
    {
        $dpl = $this->loginAs('dpl');
        $dplStudent = Mahasiswa::factory()->create(['email' => 'dpl-student@pps.test']);
        $outsideDplStudent = Mahasiswa::factory()->create(['email' => 'outside-dpl@pps.test']);
        Dplmentoring::create(['email_mahasiswa' => $dplStudent->email, 'email_dpl' => $dpl->email]);
        PendataanPemilahanSampah::factory()->count(2)->create(['email' => $dplStudent->email]);
        PendataanPemilahanSampah::factory()->count(4)->create(['email' => $outsideDplStudent->email]);

        $this->getJson('pendataanpemilahan/listdatagrouping?draw=1&start=0&length=10', $this->ajax)
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.email', $dplStudent->email)
            ->assertJsonPath('data.0.count_log', 2);

        $pt = $this->loginAs('pt', ['email' => '041111']);
        $ptStudent = Mahasiswa::factory()->create(['email' => 'pt-student@pps.test', 'kodept' => $pt->email]);
        $outsidePtStudent = Mahasiswa::factory()->create(['email' => 'outside-pt@pps.test', 'kodept' => '041112']);
        PendataanPemilahanSampah::factory()->count(5)->create(['email' => $ptStudent->email]);
        PendataanPemilahanSampah::factory()->create(['email' => $outsidePtStudent->email]);

        $this->getJson('pendataanpemilahan/listdatagrouping?draw=1&start=0&length=10', $this->ajax)
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.email', $ptStudent->email)
            ->assertJsonPath('data.0.count_log', 5);
    }

    public function test_kepala_and_pemda_can_see_grouped_population_counts(): void
    {
        $student = Mahasiswa::factory()->create(['email' => 'visible@pps.test']);
        PendataanPemilahanSampah::factory()->count(2)->create(['email' => $student->email]);

        foreach (['kepala', 'pemda'] as $role) {
            $this->loginAs($role);
            $this->getJson('pendataanpemilahan/listdatagrouping?draw=1&start=0&length=10', $this->ajax)
                ->assertOk()
                ->assertJsonPath('recordsTotal', 1)
                ->assertJsonPath('data.0.count_log', 2);
        }
    }

    public function test_student_export_filters_population_data_by_month(): void
    {
        $user = $this->loginAs('mahasiswa');
        PendataanPemilahanSampah::factory()->create(['email' => $user->email, 'tanggal' => '2026-05-12']);
        PendataanPemilahanSampah::factory()->create(['email' => $user->email, 'tanggal' => '2026-06-12']);
        Excel::fake();

        $this->get('pendataanpemilahan/export?bulan=2026-05')->assertOk();

        Excel::matchByRegex();
        Excel::assertDownloaded('/^pendataan_pemilahan_.+_2026-05_.+\.xlsx$/', function (PendataanPemilahanSampahByMhsExport $export) {
            return $export->collection()->count() === 1;
        });
    }

    public function test_reviewer_export_is_limited_to_students_they_can_view(): void
    {
        $this->loginAs('admin');
        $student = Mahasiswa::factory()->create(['email' => 'student@pps.test']);
        PendataanPemilahanSampah::factory()->create(['email' => $student->email, 'tanggal' => '2026-05-12']);
        PendataanPemilahanSampah::factory()->create(['email' => $student->email, 'tanggal' => '2026-06-12']);
        Excel::fake();

        $this->get('pendataanpemilahan/export/'.rawurlencode($student->email).'?bulan=2026-05')->assertOk();

        Excel::matchByRegex();
        Excel::assertDownloaded('/^pendataan_pemilahan_.+_2026-05_.+\.xlsx$/', function (PendataanPemilahanSampahByMhsExport $export) {
            return $export->collection()->count() === 1;
        });
    }

    private function dtColumns(array $names): array
    {
        return collect($names)->map(fn ($c) => [
            'data' => $c, 'name' => $c,
            'searchable' => $c === 'DT_RowIndex' ? 'false' : 'true',
            'orderable' => $c === 'DT_RowIndex' ? 'false' : 'true',
            'search' => ['value' => '', 'regex' => 'false'],
        ])->all();
    }

    public function test_reviewer_detail_search_memilah_and_tanggal(): void
    {
        $this->loginAs('admin');
        $m = Mahasiswa::factory()->create(['email' => 'det@pps.test']);
        PendataanPemilahanSampah::factory()->create(['email' => $m->email, 'memilah' => true, 'tanggal' => '2026-09-30']);
        PendataanPemilahanSampah::factory()->create(['email' => $m->email, 'memilah' => false, 'tanggal' => '2026-10-01']);
        $cols = $this->dtColumns(['DT_RowIndex', 'tanggal', 'nama_kepala_keluarga', 'alamat_rumah', 'rt', 'rw', 'memilah']);
        $url = 'pendataanpemilahan/listdataserver/'.rawurlencode($m->email).'?';

        foreach (['Tidak' => '01-10-2026', '30-09-2026' => '30-09-2026'] as $keyword => $tanggal) {
            $this->getJson($url.http_build_query(['draw' => 1, 'start' => 0, 'length' => 10, 'columns' => $cols,
                'search' => ['value' => $keyword, 'regex' => 'false']]), $this->ajax)
                ->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.tanggal', $tanggal);
        }
    }

    public function test_sort_tanggal_is_chronological_for_student_and_reviewer(): void
    {
        $user = $this->loginAs('mahasiswa');
        PendataanPemilahanSampah::factory()->create(['email' => $user->email, 'tanggal' => '2026-09-30']);
        PendataanPemilahanSampah::factory()->create(['email' => $user->email, 'tanggal' => '2026-10-01']);
        $params = ['draw' => 1, 'start' => 0, 'length' => 10,
            'columns' => $this->dtColumns(['DT_RowIndex', 'tanggal']), 'order' => [['column' => 1, 'dir' => 'desc']]];

        $this->getJson('pendataanpemilahan/listdataserver?'.http_build_query($params), $this->ajax)
            ->assertOk()->assertJsonPath('data.0.tanggal', '01-10-2026');

        $this->loginAs('admin');
        $this->getJson('pendataanpemilahan/listdataserver/'.rawurlencode($user->email).'?'.http_build_query($params), $this->ajax)
            ->assertOk()->assertJsonPath('data.0.tanggal', '01-10-2026');
        $params['order'][0]['dir'] = 'asc';
        $this->getJson('pendataanpemilahan/listdataserver/'.rawurlencode($user->email).'?'.http_build_query($params), $this->ajax)
            ->assertOk()->assertJsonPath('data.0.tanggal', '30-09-2026');
    }

    public function test_student_cannot_view_other_student_detail_or_export(): void
    {
        $this->loginAs('mahasiswa');
        $other = Mahasiswa::factory()->create(['email' => 'lain@pps.test']);
        PendataanPemilahanSampah::factory()->create(['email' => $other->email]);

        $this->get('pendataanpemilahan/listdata/'.rawurlencode($other->email))->assertNotFound();
        $this->getJson('pendataanpemilahan/listdataserver/'.rawurlencode($other->email).'?draw=1&start=0&length=10', $this->ajax)->assertNotFound();
        $this->get('pendataanpemilahan/export/'.rawurlencode($other->email))->assertNotFound();
        $this->getJson('pendataanpemilahan/listdatagrouping?draw=1&start=0&length=10', $this->ajax)->assertJsonPath('recordsTotal', 0);
    }

    public function test_dpl_cannot_edit_or_delete_student_records(): void
    {
        $dpl = $this->loginAs('dpl');
        $m = Mahasiswa::factory()->create(['email' => 'bimb@pps.test']);
        Dplmentoring::create(['email_mahasiswa' => $m->email, 'email_dpl' => $dpl->email]);
        $row = PendataanPemilahanSampah::factory()->create(['email' => $m->email]);

        $this->get('pendataanpemilahan/edit/'.$row->id_pendataan)->assertRedirect();
        $this->put('pendataanpemilahan/update', $this->payload(['id_pendataan' => $row->id_pendataan, 'rt' => '999']))->assertRedirect();
        $this->put('pendataanpemilahan/destroy', ['id_pendataan' => $row->id_pendataan])->assertRedirect();
        $this->assertDatabaseHas('pendataan_pemilahan_sampah', ['id_pendataan' => $row->id_pendataan, 'rt' => $row->rt]);
    }
}
