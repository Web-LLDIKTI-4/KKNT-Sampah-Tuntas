<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Dplmentoring;
use App\Models\Freeform;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Nilaikonversi;
use App\Models\Satuanpendidikan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\InspectsSortableTables;
use Tests\TestCase;

// Kontrak markup table-sort.js pada tabel Blade statis selain landing & capaian publik
class SortableTablesTest extends TestCase
{
    use InspectsSortableTables;
    use RefreshDatabase;

    private const LOKASI_TABLE = '//table[thead/tr/th[1][normalize-space()="Lokasi Kegiatan"]]';

    private const NILAI_TABLE = '//table[@data-sortable]';

    private function placeMahasiswa(Satuanpendidikan $pt, LokasiProgram $lokasi): void
    {
        $mhs = Mahasiswa::factory()->create(['kodept' => $pt->npsn, 'location_program' => $lokasi->id]);
        Mahasiswa_lokasi::create(['tahun' => (int) date('Y'), 'id_mahasiswa' => $mhs->id_mahasiswa, 'id_desa' => Desa::factory()->create()->id_desa]);
    }

    private function assertBelumLokasiFixedLast(\DOMXPath $xpath, \DOMElement $table): void
    {
        $rows = $this->bodyRows($xpath, $table);
        $last = end($rows);
        $this->assertSame('Belum memilih lokasi', trim($last->firstElementChild->textContent));
        $this->assertTrue($last->hasAttribute('data-sort-fixed'), 'Baris "Belum memilih lokasi" harus data-sort-fixed');
        $this->assertSame(0, $xpath->query('./tfoot//*[@data-sort-value] | ./tfoot//button', $table)->length, 'tfoot Total tidak ikut sort');
    }

    public function test_home_lokasi_table_is_sortable_for_kepala_with_pt_column(): void
    {
        $pt = Satuanpendidikan::factory()->create();
        $this->placeMahasiswa($pt, LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Alfa']));
        $this->placeMahasiswa($pt, LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Beta']));
        $this->loginAs('kepala');

        $xpath = $this->xpathOf($this->get('home')->assertOk()->getContent());
        $table = $this->findTable($xpath, self::LOKASI_TABLE);

        $this->assertSortableHeader($xpath, $table, [0 => 'text', 1 => 'num', 2 => 'num', 3 => 'num', 4 => 'num', 5 => 'num', 6 => 'num']);
        $data = $this->bodyRows($xpath, $table, '[not(@data-sort-fixed)]');
        $this->assertCount(2, $data);
        foreach ($data as $tr) {
            $this->assertSame(6, $xpath->query('./td[@data-sort-value]', $tr)->length);
            foreach ($xpath->query('./td[@data-sort-value]', $tr) as $td) {
                $this->assertMatchesRegularExpression('/^\d+$/', $td->getAttribute('data-sort-value'));
            }
        }
        $this->assertBelumLokasiFixedLast($xpath, $table);
    }

    public function test_home_lokasi_table_for_pt_has_no_pt_column_and_empty_state_is_fixed(): void
    {
        $pt = Satuanpendidikan::factory()->create();
        $this->loginAs('pt', ['email' => $pt->npsn]);

        $xpath = $this->xpathOf($this->get('home')->assertOk()->getContent());
        $table = $this->findTable($xpath, self::LOKASI_TABLE);

        $this->assertSortableHeader($xpath, $table, [0 => 'text', 1 => 'num', 2 => 'num', 3 => 'num', 4 => 'num', 5 => 'num']);
        $rows = $this->bodyRows($xpath, $table);
        $this->assertCount(2, $rows);
        $this->assertSame('Belum ada mahasiswa yang ditempatkan', trim($rows[0]->textContent));
        $this->assertTrue($rows[0]->hasAttribute('data-sort-fixed'), 'Baris empty-state harus data-sort-fixed');
        $this->assertBelumLokasiFixedLast($xpath, $table);
    }

    public function test_mahasiswa_nilai_tables_are_sortable(): void
    {
        $user = $this->loginAs('mahasiswa');
        $mhs = Mahasiswa::where('email', $user->email)->firstOrFail();
        Freeform::factory()->count(2)->create(['id_mahasiswa' => $mhs->id_mahasiswa, 'email_dpl' => 'dpl@pps.test']);
        Nilaikonversi::factory()->count(2)->create(['id_mahasiswa' => $mhs->id_mahasiswa, 'email_dpl' => 'dpl@pps.test']);

        foreach (['nilaifreeform/freeform' => [1 => 'text', 2 => 'num', 3 => 'num'],
            'nilaifreeform/nilaikonversi' => [1 => 'text', 2 => 'num', 3 => 'num', 4 => 'num']] as $url => $sortable) {
            $xpath = $this->xpathOf($this->get($url)->assertOk()->getContent());
            $table = $this->findTable($xpath, self::NILAI_TABLE);
            $this->assertSortableHeader($xpath, $table, $sortable);
            $rows = $this->bodyRows($xpath, $table);
            $this->assertCount(2, $rows, $url);
            foreach ($rows as $tr) {
                $this->assertSame(1, $xpath->query('./td[1][@data-sort-no]', $tr)->length, "{$url}: kolom No harus data-sort-no");
                $this->assertMatchesRegularExpression('/^\d+(\.\d+)?$/', $xpath->query('./td[last()]', $tr)->item(0)->getAttribute('data-sort-value'));
            }
        }
    }

    public function test_nilai_empty_state_is_fixed_and_pt_without_mahasiswa_gets_404(): void
    {
        $this->loginAs('mahasiswa');
        foreach (['nilaifreeform/freeform', 'nilaifreeform/nilaikonversi'] as $url) {
            $xpath = $this->xpathOf($this->get($url)->assertOk()->getContent());
            $rows = $this->bodyRows($xpath, $this->findTable($xpath, self::NILAI_TABLE));
            $this->assertCount(1, $rows);
            $this->assertTrue($rows[0]->hasAttribute('data-sort-fixed'), "{$url}: baris empty-state harus data-sort-fixed");
        }

        $this->loginAs('pt');
        $this->get('nilaifreeform/freeform')->assertNotFound();
    }

    public function test_dpl_mentoring_nilai_tables_are_sortable_and_scoped_to_mentee(): void
    {
        $dpl = $this->loginAs('dpl');
        $mhs = Mahasiswa::factory()->create();
        Dplmentoring::create(['email_mahasiswa' => $mhs->email, 'email_dpl' => $dpl->email]);
        Freeform::factory()->create(['id_mahasiswa' => $mhs->id_mahasiswa, 'email_dpl' => $dpl->email]);

        $cases = [
            'dplmentoring/freeform/' => [[1 => 'text', 2 => 'num', 3 => 'num', 4 => 'num'], 1],
            'dplmentoring/nilaikonversi/' => [[1 => 'text', 2 => 'num', 3 => 'num', 4 => 'num', 5 => 'num'], 0],
        ];
        foreach ($cases as $url => [$sortable, $jumlah]) {
            $xpath = $this->xpathOf($this->get($url.$mhs->id_mahasiswa)->assertOk()->getContent());
            $table = $this->findTable($xpath, self::NILAI_TABLE);
            $this->assertSortableHeader($xpath, $table, $sortable);
            $this->assertCount($jumlah, $this->bodyRows($xpath, $table, '[td[1][@data-sort-no]]'), $url);
            if ($jumlah === 0) {
                $this->assertTrue($this->bodyRows($xpath, $table)[0]->hasAttribute('data-sort-fixed'), "{$url}: empty-state harus data-sort-fixed");
            }
        }

        $bukanBimbingan = Mahasiswa::factory()->create();
        $this->get('dplmentoring/freeform/'.$bukanBimbingan->id_mahasiswa)->assertNotFound();
    }

    public function test_landing_sebaran_table_is_sortable_text_columns(): void
    {
        Cache::flush();
        $pt = Satuanpendidikan::factory()->create(['nm_lemb' => 'Universitas Alfa']);
        Mahasiswa::factory()->create(['kodept' => $pt->npsn, 'location_program' => LokasiProgram::factory()->create()->id]);

        $xpath = $this->xpathOf($this->get(route('landing'))->assertOk()->getContent());
        $table = $this->findTable($xpath, '//table[tbody[@id="sebaranTbody"]]');

        $this->assertSortableHeader($xpath, $table, [1 => 'text', 2 => 'text', 3 => 'text', 4 => 'text', 5 => 'text']);
        $this->assertCount(1, $this->bodyRows($xpath, $table, '[td[1][contains(@class,"peserta-no")]]'));
    }
}
