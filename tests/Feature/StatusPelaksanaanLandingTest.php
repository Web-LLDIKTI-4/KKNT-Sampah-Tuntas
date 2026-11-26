<?php

namespace Tests\Feature;

use App\Models\Mahasiswa;
use App\Models\PelaksanaanKkn;
use App\Models\Satuanpendidikan;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StatusPelaksanaanLandingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public static function boundaryProvider(): array
    {
        // Hari ini 2026-11-26
        return [
            'belum diisi' => [null, 'belum'],
            'selesai > 7 hari lagi' => ['2026-12-04', 'berjalan'],
            'tepat 7 hari lagi' => ['2026-12-03', 'akan_berakhir'],
            'selesai hari ini' => ['2026-11-26', 'akan_berakhir'],
            'selesai kemarin' => ['2026-11-25', 'berakhir'],
        ];
    }

    /** @dataProvider boundaryProvider */
    public function test_status_boundaries(?string $selesai, string $expected): void
    {
        $this->assertSame($expected, PelaksanaanKkn::statusPelaksanaan($selesai, Carbon::parse('2026-11-26 23:30:00')));
    }

    public function test_landing_renders_status_badge_per_pt(): void
    {
        Carbon::setTestNow('2026-11-26 10:00:00');

        $cases = [
            'belum' => [null, 'Belum Diisi', 'bg-label-danger'],
            'berjalan' => ['2026-12-20', 'Sedang Berjalan', 'bg-label-success'],
            'akan' => ['2026-11-30', 'Akan Berakhir', 'bg-label-orange'],
            'berakhir' => ['2026-11-20', 'Berakhir', 'bg-label-secondary'],
        ];

        $kodeByCase = [];
        foreach ($cases as $key => [$selesai]) {
            $pt = Satuanpendidikan::factory()->create();
            Mahasiswa::factory()->create(['kodept' => $pt->npsn]);
            if ($selesai) {
                PelaksanaanKkn::factory()->forPt($pt->npsn)->create(['tanggal_mulai' => '2026-11-01', 'tanggal_selesai' => $selesai]);
            }
            $kodeByCase[$key] = (string) $pt->npsn;
        }

        $html = $this->get(route('landing'))->assertOk()->getContent();
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        foreach ($cases as $key => [, $label, $class]) {
            $badge = $xpath->query('//tbody[@id="pesertaTbody"]/tr[td[2]="'.$kodeByCase[$key].'"]/td[last()]/span')->item(0);
            $this->assertNotNull($badge, "Badge {$key} tidak ditemukan");
            $this->assertSame($label, trim($badge->textContent));
            $this->assertStringContainsString($class, $badge->getAttribute('class'));
        }
    }
}
