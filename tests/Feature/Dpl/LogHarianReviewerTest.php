<?php

namespace Tests\Feature\Dpl;

use App\Models\Dplmentoring;
use App\Models\Logkegiatan;
use App\Models\Mahasiswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Detail log harian per mahasiswa untuk reviewer (admlogkegiatan)
class LogHarianReviewerTest extends TestCase
{
    use RefreshDatabase;

    private array $ajax = ['X-Requested-With' => 'XMLHttpRequest'];

    private function detail(string $email, array $params = [])
    {
        $cols = [['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'searchable' => 'false', 'orderable' => 'false'],
            ['data' => 'tanggal', 'name' => 'tanggal', 'searchable' => 'true', 'orderable' => 'true']];

        return $this->getJson('admlogkegiatan/listdataserver/'.rawurlencode($email).'?'.http_build_query($params + [
            'draw' => 1, 'start' => 0, 'length' => 10, 'columns' => $cols,
        ]), $this->ajax);
    }

    public function test_sort_dan_search_tanggal_kronologis(): void
    {
        $this->loginAs('admin');
        $m = Mahasiswa::factory()->create(['email' => 'sortlog@pps.test']);
        Logkegiatan::factory()->create(['email' => $m->email, 'tanggal' => '2026-09-30']);
        Logkegiatan::factory()->create(['email' => $m->email, 'tanggal' => '2026-10-01']);

        $this->detail($m->email, ['order' => [['column' => 1, 'dir' => 'desc']]])->assertOk()->assertJsonPath('data.0.tanggal', '01-10-2026');
        $this->detail($m->email, ['order' => [['column' => 1, 'dir' => 'asc']]])->assertOk()->assertJsonPath('data.0.tanggal', '30-09-2026');
        $this->detail($m->email, ['search' => ['value' => '30-09-2026', 'regex' => 'false']])->assertOk()->assertJsonPath('recordsFiltered', 1);
    }

    public function test_dpl_hanya_mahasiswa_bimbingan(): void
    {
        $dpl = $this->loginAs('dpl');
        $luar = Mahasiswa::factory()->create(['email' => 'luar@pps.test']);
        $bimb = Mahasiswa::factory()->create(['email' => 'bimb@pps.test']);
        Dplmentoring::create(['email_mahasiswa' => $bimb->email, 'email_dpl' => $dpl->email]);

        $this->detail($luar->email)->assertNotFound();
        $this->get('admlogkegiatan/export/'.rawurlencode($luar->email))->assertNotFound();
        $this->detail($bimb->email)->assertOk();
    }
}
