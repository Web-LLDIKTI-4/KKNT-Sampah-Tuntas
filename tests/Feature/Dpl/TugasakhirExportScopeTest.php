<?php

namespace Tests\Feature\Dpl;

use App\Models\Dplmentoring;
use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use App\Models\Tugasakhir;
use App\Models\User;
use App\Support\XlsxStreamer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

class TugasakhirExportScopeTest extends TestCase
{
    use RefreshDatabase;

    private array $ajax = ['X-Requested-With' => 'XMLHttpRequest'];

    private Satuanpendidikan $ptA;

    private Satuanpendidikan $ptB;

    private User $dplA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ptA = Satuanpendidikan::factory()->create(['nm_lemb' => 'Universitas A']);
        $this->ptB = Satuanpendidikan::factory()->create(['nm_lemb' => 'Universitas B']);
        $this->dplA = User::factory()->role('dpl')->create();

        $this->tugas('2001', 'Mhs A1', $this->ptA, $this->dplA);
        $this->tugas('2002', 'Mhs A2', $this->ptA);
        $this->tugas('3001', 'Mhs B1', $this->ptB);
        $this->tugas('3002', 'Mhs B2 100%', $this->ptB, User::factory()->role('dpl')->create());
    }

    private function tugas(string $nim, string $nama, Satuanpendidikan $pt, ?User $dpl = null): Tugasakhir
    {
        $mhs = Mahasiswa::factory()->create(['nim' => $nim, 'nama' => $nama, 'kodept' => $pt->npsn]);
        if ($dpl) {
            Dplmentoring::create(['email_mahasiswa' => $mhs->email, 'email_dpl' => $dpl->email]);
        }

        return Tugasakhir::factory()->create(['email' => $mhs->email, 'nilai_dpl' => 80]);
    }

    // Header row + data rows (title & info rows skipped)
    private function exportRows(TestResponse $response): array
    {
        $path = tempnam(sys_get_temp_dir(), 'tugasakhir');
        try {
            file_put_contents($path, $response->assertOk()->streamedContent());
            $reader = new Reader;
            $reader->open($path);
            $rows = [];
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $i => $row) {
                    if ($i === 1) {
                        $this->assertSame('Laporan Tugas Akhir', $row->getCells()[0]->getValue());
                    }
                    if ($i >= XlsxStreamer::HEADER_ROW) {
                        $rows[] = array_map(fn ($cell) => $cell->getValue(), $row->getCells());
                    }
                }
            }
            $reader->close();
        } finally {
            unlink($path);
        }

        return $rows;
    }

    private function nims(array $rows): array
    {
        $nims = array_column(array_slice($rows, 1), 1);
        sort($nims);

        return $nims;
    }

    private function listNims(string $query = ''): array
    {
        $columns = '&columns[0][data]=nim&columns[0][name]=nim&columns[0][searchable]=true&columns[0][orderable]=true'
            .'&columns[1][data]=nama&columns[1][name]=nama&columns[1][searchable]=true&columns[1][orderable]=true';
        $data = $this->getJson('dpllaptugasakhir/listdataserver?draw=1&start=0&length=50'.$columns.$query, $this->ajax)
            ->assertOk()->json('data');
        $nims = array_map(fn ($row) => strip_tags($row['nim']), $data);
        sort($nims);

        return $nims;
    }

    public function test_pt_export_and_list_only_contain_own_pt(): void
    {
        $this->loginAs('pt', ['email' => $this->ptA->npsn]);

        $rows = $this->exportRows($this->get('dpllaptugasakhir/export'));
        $this->assertSame(['No.', 'NIM', 'Nama', 'Perguruan Tinggi', 'Tautan', 'Nilai'], $rows[0]);
        $this->assertSame(['2001', '2002'], $this->nims($rows));
        $this->assertSame('Universitas A', $rows[1][3]);

        $this->assertSame(['2001', '2002'], $this->listNims());
        // Search tidak bisa keluar dari scope PT
        $this->assertSame([], $this->listNims('&search[value]=Mhs B1'));
    }

    public function test_pt_without_matching_mahasiswa_gets_empty_export(): void
    {
        $this->loginAs('pt', ['email' => 'npsn-lain']);

        $this->assertSame([], $this->nims($this->exportRows($this->get('dpllaptugasakhir/export'))));
    }

    public function test_dpl_export_and_list_only_contain_mentees(): void
    {
        $this->actingAs($this->dplA);

        $this->assertSame(['2001'], $this->nims($this->exportRows($this->get('dpllaptugasakhir/export'))));
        $this->assertSame(['2001'], $this->listNims());
        $this->assertSame([], $this->listNims('&search[value]=Mhs A2'));
    }

    public function test_dpl_list_shows_grade_form_but_pt_does_not(): void
    {
        $this->actingAs($this->dplA);
        $action = $this->getJson('dpllaptugasakhir/listdataserver?draw=1', $this->ajax)->json('data.0.action');
        $this->assertStringContainsString('name="nilai_dpl"', $action);

        $this->loginAs('pt', ['email' => $this->ptA->npsn]);
        $this->assertSame('', $this->getJson('dpllaptugasakhir/listdataserver?draw=1', $this->ajax)->json('data.0.action'));
    }

    public function test_kepala_and_pemda_export_all(): void
    {
        foreach (['kepala', 'pemda'] as $role) {
            $this->loginAs($role);
            $this->assertSame(['2001', '2002', '3001', '3002'], $this->nims($this->exportRows($this->get('dpllaptugasakhir/export'))));
        }
    }

    public function test_admin_export_follows_search_with_escaped_like(): void
    {
        $this->loginAs('admin');
        $columns = '&columns[0][data]=nama&columns[0][name]=nama&columns[0][searchable]=true&columns[0][orderable]=true';

        // "_" di-escape: bukan wildcard satu karakter
        $this->assertSame([], $this->nims($this->exportRows($this->get('laptugasakhir/export?search[value]=Mhs_A'.$columns))));
        $this->assertSame(['2001', '2002'], $this->nims($this->exportRows($this->get('laptugasakhir/export?search[value]=Mhs A'.$columns))));

        $rows = $this->exportRows($this->get('laptugasakhir/export?order[0][column]=0&order[0][dir]=desc'.$columns));
        $this->assertSame(['Mhs B2 100%', 'Mhs B1', 'Mhs A2', 'Mhs A1'], array_column(array_slice($rows, 1), 2));
        $this->assertSame(80, (int) $rows[1][5]);
    }

    public function test_admin_list_orders_by_relation_columns_without_error(): void
    {
        $this->loginAs('admin');
        $columns = '&columns[0][data]=nim&columns[0][name]=nim&columns[0][orderable]=true'
            .'&columns[1][data]=nm_lemb&columns[1][name]=nm_lemb&columns[1][orderable]=true';

        $data = $this->getJson('laptugasakhir/listdataserver?draw=1&order[0][column]=1&order[0][dir]=desc&order[1][column]=0&order[1][dir]=asc'.$columns, $this->ajax)
            ->assertOk()->json('data');
        $this->assertSame(['3001', '3002', '2001', '2002'], array_column($data, 'nim'));
    }

    public function test_mahasiswa_cannot_export(): void
    {
        $this->loginAs('mahasiswa');
        $this->get('dpllaptugasakhir/export')->assertRedirect(route('home'));
        $this->get('laptugasakhir/export')->assertRedirect(route('home'));
    }
}
