<?php

namespace Tests\Feature\Auth;

use App\Models\Dpl;
use App\Models\Mahasiswa;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataPesertaSortTest extends TestCase
{
    use RefreshDatabase;

    private const SORT_KEYS = ['kodept', 'nama', 'mahasiswa', 'dpl', 'pic', 'ketua'];

    private Satuanpendidikan $pt;

    private Satuanpendidikan $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pt = Satuanpendidikan::factory()->create(['nm_lemb' => 'Universitas "Alfa" & <Co>']);
        $this->other = Satuanpendidikan::factory()->create(['nm_lemb' => 'Politeknik Beta']);

        Mahasiswa::factory()->count(2)->create(['kodept' => $this->pt->npsn]);
        $ketua = Mahasiswa::factory()->create(['kodept' => $this->pt->npsn]);
        Pjdesa::create(['email' => $ketua->email]);
        Dpl::factory()->count(2)->create(['kodept' => $this->pt->npsn]);
        User::factory()->role('pt')->create(['email' => $this->pt->npsn]);

        Dpl::factory()->create(['kodept' => $this->other->npsn]);
        // PT absent from ref_satuanpendidikan falls back to kodept as nama_pt
        Mahasiswa::factory()->create(['kodept' => 'XK01']);
    }

    private function pesertaTable(): DOMXPath
    {
        $html = $this->get(route('landing'))->assertOk()->getContent();
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        return new DOMXPath($dom);
    }

    private function sortAttrsByKode(DOMXPath $xpath): array
    {
        $rows = [];
        foreach ($xpath->query('//tbody[@id="pesertaTbody"]/tr') as $tr) {
            $attrs = [];
            foreach (self::SORT_KEYS as $i => $key) {
                $td = $xpath->query('./td['.($i + 2).']', $tr)->item(0);
                $this->assertNotNull($td, "Row missing td #".($i + 1));
                $this->assertTrue($td->hasAttribute('data-sort-value'), "td #".($i + 1)." ({$key}) missing data-sort-value");
                $attrs[$key] = $td->getAttribute('data-sort-value');
            }
            $rows[$attrs['kodept']] = $attrs;
        }

        return $rows;
    }

    public function test_header_has_six_sort_buttons_with_type(): void
    {
        $xpath = $this->pesertaTable();
        $ths = $xpath->query('//table[tbody[@id="pesertaTbody"]]/thead/tr/th');

        $this->assertSame(7, $ths->length);
        $this->assertSame(0, $xpath->query('.//button', $ths->item(0))->length, 'Kolom No tidak boleh sortable');

        $expected = [1 => 'text', 2 => 'text', 3 => 'num', 4 => 'num', 5 => 'num', 6 => 'num'];
        foreach ($expected as $i => $type) {
            $buttons = $xpath->query('./button[contains(@class,"table-sort")]', $ths->item($i));
            $this->assertSame(1, $buttons->length, "th #{$i} harus punya satu tombol sort");
            $this->assertSame('button', $buttons->item(0)->getAttribute('type'));
            $this->assertSame($type, $buttons->item(0)->getAttribute('data-sort-type'));
        }

        $this->assertSame(6, $xpath->query('//table[tbody[@id="pesertaTbody"]]//button[contains(@class,"table-sort")]')->length);
    }

    public function test_header_text_is_unchanged(): void
    {
        $headers = array_map(
            fn ($th) => trim($th->textContent),
            iterator_to_array($this->pesertaTable()->query('//table[tbody[@id="pesertaTbody"]]/thead/tr/th'))
        );

        $this->assertSame(['No', 'Kode PT', 'Nama PTS', 'Mahasiswa', 'DPL', 'PIC PTS', 'Ketua Kelompok'], $headers);
    }

    public function test_each_row_has_sort_attributes_matching_peserta_data(): void
    {
        $rows = $this->sortAttrsByKode($this->pesertaTable());
        $pt = (string) $this->pt->npsn;
        $other = (string) $this->other->npsn;

        $this->assertEqualsCanonicalizing([$pt, $other, 'XK01'], array_keys($rows));
        $this->assertSame(
            ['kodept' => $pt, 'nama' => 'Universitas "Alfa" & <Co>', 'mahasiswa' => '3', 'dpl' => '2', 'pic' => '1', 'ketua' => '1'],
            $rows[$pt]
        );
        $this->assertSame(
            ['kodept' => $other, 'nama' => 'Politeknik Beta', 'mahasiswa' => '0', 'dpl' => '1', 'pic' => '0', 'ketua' => '0'],
            $rows[$other]
        );
        $this->assertSame(
            ['kodept' => 'XK01', 'nama' => 'XK01', 'mahasiswa' => '1', 'dpl' => '0', 'pic' => '0', 'ketua' => '0'],
            $rows['XK01']
        );
    }

    public function test_numeric_sort_attributes_are_plain_integers(): void
    {
        foreach ($this->sortAttrsByKode($this->pesertaTable()) as $kode => $attrs) {
            foreach (['mahasiswa', 'dpl', 'pic', 'ketua'] as $key) {
                $this->assertMatchesRegularExpression('/^\d+$/', $attrs[$key], "{$kode} data-sort-{$key} bukan integer");
            }
        }
    }

    public function test_nama_sort_attribute_is_html_escaped_in_source(): void
    {
        $html = $this->get(route('landing'))->assertOk()->getContent();

        $this->assertStringContainsString('data-sort-value="Universitas &quot;Alfa&quot; &amp; &lt;Co&gt;"', $html);
    }

    public function test_footer_is_outside_tbody_so_it_is_not_sorted(): void
    {
        $xpath = $this->pesertaTable();

        $this->assertSame(0, $xpath->query('//tbody[@id="pesertaTbody"]//th')->length);
        $this->assertSame('Total', trim($xpath->query('//table[tbody[@id="pesertaTbody"]]/tfoot/tr/th')->item(0)->textContent));
        $this->assertSame(1, $xpath->query('//table[tbody[@id="pesertaTbody"]][@data-sortable]')->length);
        $this->assertSame(0, $xpath->query('//table[tbody[@id="pesertaTbody"]]/tfoot//*[@data-sort-value]')->length);
    }
}
