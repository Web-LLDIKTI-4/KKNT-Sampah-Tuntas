<?php

namespace Tests\Feature;

use App\Exports\DataSampahExport;
use App\Models\Desa;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\PendataanPemilahanSampah;
use App\Models\Satuanpendidikan;
use App\Services\PenguranganSampahService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\ReadsXlsx;
use Tests\TestCase;

class DataSampahTest extends TestCase
{
    use ReadsXlsx;
    use RefreshDatabase;

    private const BULAN = '2026-09';

    private const ROW_KEYS = [
        'lokasi_id', 'nama_lokasi', 'koefisien', 'bulan', 'nama_bulan', 'minggu', 'minggu_label', 'tanggal',
        'kecamatan', 'desa', 'nama_kepala_keluarga', 'alamat_rumah', 'jml_rumah', 'jml_rumah_memilah', 'persen_ketaatan',
        'organik', 'anorganik', 'residu', 'jml_jiwa', 'total_terkelola', 'total_dihasilkan', 'persen_pengelolaan',
        'persen_penurunan_timbulan',
    ];

    private function mahasiswaDi(Desa $desa, ?LokasiProgram $lokasi = null, ?string $kodept = null): Mahasiswa
    {
        $mhs = Mahasiswa::factory()->create(['kodept' => $kodept, 'location_program' => $lokasi?->id]);
        Mahasiswa_lokasi::create(['tahun' => (int) date('Y'), 'id_mahasiswa' => $mhs->id_mahasiswa, 'id_desa' => $desa->id_desa, 'user_in_up' => $mhs->email]);

        return $mhs;
    }

    private function log(Mahasiswa $mhs, array $attr = []): PendataanPemilahanSampah
    {
        return PendataanPemilahanSampah::factory()->create($attr + [
            'email' => $mhs->email, 'tanggal' => self::BULAN.'-10',
            'nama_kepala_keluarga' => 'Budi', 'alamat_rumah' => 'Jl. Melati 1', 'rt' => '001', 'rw' => '002',
            'jumlah_anggota_keluarga' => 4, 'memilah' => true, 'organik_kg' => 1, 'anorganik_kg' => 1, 'residu_kg' => 1,
        ]);
    }

    private function list(array $params = []): TestResponse
    {
        return $this->getJson(route('rekapsampah.listdataserver', $params + ['bulan' => 'semua', 'draw' => 1, 'start' => 0, 'length' => 100]),
            ['X-Requested-With' => 'XMLHttpRequest']);
    }

    private function rows(array $params = []): array
    {
        return $this->list($params)->assertOk()->json('data');
    }

    public function test_halaman_rekapsampah_200_dan_kolom_data_sampah_per_role(): void
    {
        $lokasi = LokasiProgram::factory()->create(['koefisien_timbulan_sampah' => 0.5]);
        $pt = Satuanpendidikan::factory()->create();
        $this->log($this->mahasiswaDi(Desa::factory()->create(), $lokasi, $pt->npsn));

        foreach (['admin' => [], 'kepala' => [], 'pemda' => [], 'pt' => ['email' => $pt->npsn]] as $role => $attr) {
            $this->loginAs($role, $attr);
            $res = $this->get('rekapsampah?bulan=semua')->assertOk()->assertViewHas('subtotal')->assertViewHas('lokasiList');
            $kolom = $res->viewData('kolomDataSampah');
            $pemda = $role === 'pemda';

            $this->assertSame($pemda, $res->viewData('mingguan'), $role);
            $this->assertCount($pemda ? 18 : 16, $kolom, $role);
            $this->assertSame(DataSampahExport::kolom($pemda), $kolom, $role);
            $this->assertSame(array_map(fn ($i) => chr(65 + $i), array_keys($kolom)), array_column($kolom, 'huruf'));
            $keys = array_column($kolom, 'data');
            $this->assertSame($pemda ? ['nama_bulan', 'minggu_label', 'tanggal'] : ['tanggal', 'kecamatan', 'desa'], array_slice($keys, 0, 3), $role);
            $this->assertSame('persen_penurunan_timbulan', end($keys));
            // Huruf rumus bergeser +2 untuk PEMDA
            $ketaatan = $kolom[array_search('persen_ketaatan', $keys)]['label'];
            $this->assertStringContainsString($pemda ? '(I/H)' : '(G/F)', $ketaatan, $role);
            $this->assertStringContainsString($pemda ? '(1-(P/(koefisien*N)))' : '(1-(N/(koefisien*L)))', end($kolom)['label'], $role);
        }
    }

    public function test_listdataserver_struktur_json_sesuai_kontrak(): void
    {
        $lokasi = LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Uji', 'koefisien_timbulan_sampah' => 0.5]);
        $desa = Desa::factory()->create(['desa' => 'Desa Json']);
        $this->log($this->mahasiswaDi($desa, $lokasi), ['tanggal' => self::BULAN.'-09', 'nama_kepala_keluarga' => '<b>Budi</b>']);
        $this->log($this->mahasiswaDi($desa), ['tanggal' => self::BULAN.'-30', 'alamat_rumah' => 'Jl. Lain 2']);
        $this->loginAs('admin');

        $json = $this->list()->assertOk()->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data'])->json();
        $this->assertSame(2, $json['recordsTotal']);
        $this->assertCount(2, $json['data']);
        foreach ($json['data'] as $row) {
            $this->assertEqualsCanonicalizing(self::ROW_KEYS, array_keys($row));
        }

        [$a, $b] = $json['data'];
        // Lokasi bernama dulu, "Tanpa Lokasi Program" terakhir
        $this->assertSame($lokasi->id, $a['lokasi_id']);
        $this->assertSame('Kota Uji', $a['nama_lokasi']);
        $this->assertSame(0.5, $a['koefisien']);
        $this->assertSame('2026-09', $a['bulan']);
        $this->assertSame('September 2026', $a['nama_bulan']);
        $this->assertSame(2, $a['minggu']);
        $this->assertSame('Minggu 2 (8–14)', $a['minggu_label']);
        $this->assertSame('09-09-2026', $a['tanggal']);
        $this->assertSame('Desa Json', $a['desa']);
        // Tidak di-escape server (escape di klien)
        $this->assertSame('<b>Budi</b>', $a['nama_kepala_keluarga']);
        $this->assertSame(4, $a['jml_jiwa']);
        $this->assertNull($b['lokasi_id']);
        $this->assertSame('Tanpa Lokasi Program', $b['nama_lokasi']);
        $this->assertNull($b['koefisien']);
        $this->assertNull($b['persen_penurunan_timbulan']);
        $this->assertSame('Minggu 5 (29–30)', $b['minggu_label']);

        // Search hanya nama KK / alamat / desa
        $this->assertCount(1, $this->rows(['search' => ['value' => 'Jl. Lain']]));
        $this->assertCount(2, $this->rows(['search' => ['value' => 'Desa Json']]));
    }

    public function test_listdataserver_hanya_ajax_dan_role_terbatas(): void
    {
        $this->loginAs('admin');
        $this->get(route('rekapsampah.listdataserver'))->assertNotFound();

        foreach (['mahasiswa', 'dpl'] as $role) {
            $this->loginAs($role);
            $this->list()->assertStatus(302);
        }
        auth()->logout();
        $this->list()->assertStatus(401);
    }

    public function test_pt_hanya_melihat_data_pt_sendiri_walau_kirim_kodept_atau_lokasi_lain(): void
    {
        [$ptA, $ptB] = [Satuanpendidikan::factory()->create(), Satuanpendidikan::factory()->create()];
        [$lokA, $lokB] = [LokasiProgram::factory()->create(), LokasiProgram::factory()->create()];
        $desa = Desa::factory()->create();
        $this->log($this->mahasiswaDi($desa, $lokA, $ptA->npsn), ['nama_kepala_keluarga' => 'Milik A', 'alamat_rumah' => 'Jl. A 1']);
        $this->log($this->mahasiswaDi($desa, $lokB, $ptB->npsn), ['nama_kepala_keluarga' => 'Milik B', 'alamat_rumah' => 'Jl. B 1']);

        $this->loginAs('pt', ['email' => $ptA->npsn]);
        $this->assertSame(['Milik A'], array_column($this->rows(), 'nama_kepala_keluarga'));
        $this->assertSame(['Milik A'], array_column($this->rows(['kodept' => $ptB->npsn]), 'nama_kepala_keluarga'));
        $this->assertSame([], $this->rows(['lokasi' => $lokB->id]));
        $this->assertSame([], $this->rows(['kodept' => $ptB->npsn, 'lokasi' => $lokB->id]));

        // Halaman & subtotal juga terkunci
        $subtotal = $this->get('rekapsampah?bulan=semua&kodept='.$ptB->npsn)->assertOk()->viewData('subtotal');
        $this->assertSame([$lokA->id], $subtotal->pluck('id')->all());
        // PT tidak boleh export
        $this->get('rekapsampah/export')->assertRedirect(route('home'));
    }

    public function test_filter_lokasi_dan_validasi_uuid(): void
    {
        [$lokA, $lokB] = [LokasiProgram::factory()->create(), LokasiProgram::factory()->create()];
        $desa = Desa::factory()->create();
        $this->log($this->mahasiswaDi($desa, $lokA), ['alamat_rumah' => 'Jl. A 1']);
        $this->log($this->mahasiswaDi($desa, $lokB), ['alamat_rumah' => 'Jl. B 1']);
        $this->log($this->mahasiswaDi($desa, $lokB), ['alamat_rumah' => 'Jl. B 2']);
        $this->loginAs('admin');

        $this->assertCount(3, $this->rows());
        $this->assertSame([$lokB->id, $lokB->id], array_column($this->rows(['lokasi' => $lokB->id]), 'lokasi_id'));
        $this->assertSame([$lokB->id], $this->get('rekapsampah?bulan=semua&lokasi='.$lokB->id)->assertOk()->viewData('subtotal')->pluck('id')->all());

        foreach (['bukan-uuid', "' OR 1=1 --", '00000000-0000-4000-8000-000000000000'] as $invalid) {
            $this->list(['lokasi' => $invalid])->assertStatus(422)->assertJsonValidationErrors('lokasi');
        }
    }

    public function test_flag_rumah_log_pertama_per_kelurahan_bulan_dan_subtotal_sama_dedup(): void
    {
        $lokasi = LokasiProgram::factory()->create();
        $desa = Desa::factory()->create();
        $desaLain = Desa::factory()->create();
        [$a, $b] = [$this->mahasiswaDi($desa, $lokasi), $this->mahasiswaDi($desa, $lokasi)];

        // Rumah Melati: 3 log, log pertama tgl 05 tidak memilah; rumah tetap memilah (ada log memilah)
        $this->log($a, ['tanggal' => self::BULAN.'-10', 'memilah' => true]);
        $this->log($b, ['tanggal' => self::BULAN.'-05', 'memilah' => false, 'nama_kepala_keluarga' => 'Siti', 'rt' => '009']);
        $this->log($a, ['tanggal' => self::BULAN.'-20', 'memilah' => false, 'alamat_rumah' => 'JL. MELATI, 1']);
        // Rumah Mawar: tidak memilah
        $this->log($a, ['tanggal' => self::BULAN.'-12', 'memilah' => false, 'alamat_rumah' => 'Jl. Mawar 2']);
        // Alamat sama di bulan lain & kelurahan lain = rumah baru
        $this->log($a, ['tanggal' => '2026-10-02', 'memilah' => false]);
        $this->log($this->mahasiswaDi($desaLain, $lokasi), ['tanggal' => self::BULAN.'-15']);
        $this->loginAs('admin');

        $rows = collect($this->rows())->where('bulan', self::BULAN)->where('desa', $desa->desa)->keyBy('tanggal');
        $this->assertSame(1, $rows['05-09-2026']['jml_rumah']);
        $this->assertSame(1, $rows['05-09-2026']['jml_rumah_memilah']);
        $this->assertEquals(100.0, $rows['05-09-2026']['persen_ketaatan']);
        foreach (['10-09-2026', '20-09-2026'] as $tgl) {
            $this->assertSame(0, $rows[$tgl]['jml_rumah'], $tgl);
            $this->assertSame(0, $rows[$tgl]['jml_rumah_memilah'], $tgl);
            $this->assertNull($rows[$tgl]['persen_ketaatan'], $tgl);
        }
        $this->assertSame([1, 0], [$rows['12-09-2026']['jml_rumah'], $rows['12-09-2026']['jml_rumah_memilah']]);
        $this->assertSame(1, collect($this->rows())->firstWhere('bulan', '2026-10')['jml_rumah']);

        // Σ baris = subtotal bulan = dedup rekapLldikti/total()
        $sampah = app(PenguranganSampahService::class);
        $semua = collect($this->rows());
        $subtotal = $sampah->dataSampahSubtotal(['bulan' => null], false)->first();
        $sep = $subtotal->bulan->firstWhere('bulan', self::BULAN)->subtotal;
        $sepRows = $semua->where('bulan', self::BULAN);
        $total = $sampah->total(['bulan' => self::BULAN]);

        $this->assertSame(3, $sep->jml_rumah);
        $this->assertSame($sepRows->sum('jml_rumah'), $sep->jml_rumah);
        $this->assertSame($sepRows->sum('jml_rumah_memilah'), $sep->jml_rumah_memilah);
        $this->assertSame((int) $total->jml_rumah, $sep->jml_rumah);
        $this->assertSame((int) $total->jml_rumah_memilah, $sep->jml_rumah_memilah);
        $lldikti = $sampah->rekapLldikti([])->firstWhere('bulan', self::BULAN)->kecamatan->flatMap->desa->firstWhere('id_desa', $desa->id_desa);
        $this->assertSame($lldikti->jml_rumah, $sepRows->where('desa', $desa->desa)->sum('jml_rumah'));
        $this->assertEquals((float) $total->organik, $sep->organik);
        $this->assertSame($semua->sum('jml_rumah'), $subtotal->total->jml_rumah);
        $this->assertSame((int) $sampah->total(['bulan' => null])->jml_rumah, $subtotal->total->jml_rumah);
    }

    public function test_flag_rumah_tanggal_sama_memakai_created_at(): void
    {
        $desa = Desa::factory()->create();
        $mhs = $this->mahasiswaDi($desa);
        $this->log($mhs, ['nama_kepala_keluarga' => 'Kedua', 'created_at' => now()->subHour()]);
        $this->log($mhs, ['nama_kepala_keluarga' => 'Ketiga', 'created_at' => now()]);
        $this->log($mhs, ['nama_kepala_keluarga' => 'Pertama', 'created_at' => now()->subHours(2)]);
        $this->loginAs('admin');

        $flag = collect($this->rows())->pluck('jml_rumah', 'nama_kepala_keluarga')->all();
        $this->assertSame(['Kedua' => 0, 'Ketiga' => 0, 'Pertama' => 1], collect($flag)->sortKeys()->all());
    }

    public function test_persen_penurunan_timbulan_per_baris_dan_subtotal(): void
    {
        $lokasi = LokasiProgram::factory()->create(['nama_lokasi' => 'A Lokasi', 'koefisien_timbulan_sampah' => 0.5]);
        $tanpaKoef = LokasiProgram::factory()->create(['nama_lokasi' => 'B Lokasi', 'koefisien_timbulan_sampah' => null]);
        $nolKoef = LokasiProgram::factory()->create(['nama_lokasi' => 'C Lokasi', 'koefisien_timbulan_sampah' => 0]);
        $desa = Desa::factory()->create();
        $mhs = $this->mahasiswaDi($desa, $lokasi);

        // koef 0.5 × 4 jiwa = 2 → N 1 = 50%, N 3 = -50%, jiwa null / 0 = "-"
        $this->log($mhs, ['alamat_rumah' => 'R1', 'organik_kg' => 0.5, 'anorganik_kg' => 0.25, 'residu_kg' => 0.25]);
        $this->log($mhs, ['alamat_rumah' => 'R2', 'organik_kg' => 1, 'anorganik_kg' => 1, 'residu_kg' => 1]);
        $this->log($mhs, ['alamat_rumah' => 'R3', 'jumlah_anggota_keluarga' => null, 'organik_kg' => 9]);
        $this->log($mhs, ['alamat_rumah' => 'R4', 'jumlah_anggota_keluarga' => 0, 'organik_kg' => 9]);
        $this->log($this->mahasiswaDi($desa, $tanpaKoef), ['alamat_rumah' => 'R5']);
        $this->log($this->mahasiswaDi($desa, $nolKoef), ['alamat_rumah' => 'R6']);
        $this->loginAs('admin');

        $p = collect($this->rows())->pluck('persen_penurunan_timbulan', 'alamat_rumah');
        $this->assertEquals(50.0, $p['R1']);
        $this->assertEquals(-50.0, $p['R2']);
        $this->assertNull($p['R3']);
        $this->assertNull($p['R4']);
        $this->assertNull($p['R5']);
        $this->assertNull($p['R6']);
        $this->assertNull(collect($this->rows())->firstWhere('alamat_rumah', 'R3')['jml_jiwa']);

        // Subtotal: 1 - ΣN(baris berjiwa) / (koef × ΣL) = 1 - (1 + 3) / (0.5 × 8) = 0%
        $sub = app(PenguranganSampahService::class)->dataSampahSubtotal(['bulan' => null], false)->keyBy('nama');
        $this->assertSame(8, $sub['A Lokasi']->total->jml_jiwa);
        $this->assertEquals(0.0, $sub['A Lokasi']->total->persen_penurunan_timbulan);
        $this->assertNull($sub['B Lokasi']->total->persen_penurunan_timbulan);
        $this->assertNull($sub['C Lokasi']->total->persen_penurunan_timbulan);

        // Ringkasan lintas lokasi: hanya baris berjiwa & berkoefisien
        $total = app(PenguranganSampahService::class)->total(['bulan' => null]);
        $this->assertEquals(0.0, $total->persen_penurunan_timbulan);
        $this->assertSame(16, $total->jml_jiwa);

        // Fungsi murni barisLog: koef null / jiwa 0
        $r = (object) ['is_pertama' => 1, 'is_memilah' => 0, 'organik_kg' => 1, 'anorganik_kg' => 0, 'residu_kg' => 0, 'jiwa' => 2];
        $this->assertNull(PenguranganSampahService::barisLog($r, null)['persen_penurunan_timbulan']);
        $this->assertEquals(0.0, PenguranganSampahService::barisLog($r, 0.5)['persen_penurunan_timbulan']);
        $this->assertNull(PenguranganSampahService::barisLog((object) (['jiwa' => 0] + (array) $r), 0.5)['persen_penurunan_timbulan']);
    }

    public function test_minggu_per_7_tanggal_dan_sigma_minggu_sama_bulan(): void
    {
        $lokasi = LokasiProgram::factory()->create();
        $mhs = $this->mahasiswaDi(Desa::factory()->create(), $lokasi);
        $map = ['01' => 1, '07' => 1, '08' => 2, '14' => 2, '15' => 3, '21' => 3, '22' => 4, '28' => 4, '29' => 5, '31' => 5];
        foreach (array_keys($map) as $i => $tgl) {
            // Alamat Melati muncul lagi di minggu 3: rumahnya tetap dihitung di minggu 1
            $this->log($mhs, ['tanggal' => '2026-10-'.$tgl, 'alamat_rumah' => (string) $tgl === '15' ? 'Jl. Melati 1' : 'Rumah '.$i, 'organik_kg' => $i + 1]);
        }
        $this->log($mhs, ['tanggal' => '2026-10-03', 'alamat_rumah' => 'Jl. Melati 1']);
        $this->loginAs('pemda');

        $rows = collect($this->rows());
        foreach ($map as $tgl => $minggu) {
            $this->assertSame($minggu, $rows->firstWhere('tanggal', $tgl.'-10-2026')['minggu'], "tgl $tgl");
        }
        $this->assertSame('Minggu 5 (29–31)', $rows->firstWhere('tanggal', '31-10-2026')['minggu_label']);
        $this->assertSame(0, $rows->firstWhere('tanggal', '15-10-2026')['jml_rumah']);
        $this->assertSame('Minggu 1 (1–7)', PenguranganSampahService::labelMinggu(1, '2026-10-01'));

        $bulan = $this->get('rekapsampah?bulan=2026-10')->assertOk()->viewData('subtotal')->first()->bulan->first();
        $this->assertSame([1, 2, 3, 4, 5], $bulan->minggu->pluck('minggu')->all());
        $this->assertSame([3, 2, 1, 2, 2], $bulan->minggu->map(fn ($m) => $m->subtotal->jml_rumah)->all());
        foreach (['jml_rumah', 'jml_rumah_memilah', 'organik', 'anorganik', 'residu', 'jml_jiwa', 'total_dihasilkan'] as $key) {
            $this->assertEquals($bulan->subtotal->$key, $bulan->minggu->sum(fn ($m) => $m->subtotal->$key), $key);
        }
        $this->assertSame(10, $bulan->subtotal->jml_rumah);

        // Admin tidak berbagi cache dengan PEMDA: tanpa minggu
        $this->loginAs('admin');
        $this->assertTrue($this->get('rekapsampah?bulan=2026-10')->assertOk()->viewData('subtotal')->first()->bulan->first()->minggu->isEmpty());
    }

    public function test_export_data_sampah_header_dari_kolom_dan_baris_pemda_punya_bulan_minggu(): void
    {
        $lokasi = LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Export', 'koefisien_timbulan_sampah' => 0.5]);
        $desa = Desa::factory()->create(['desa' => 'Desa Export']);
        $mhs = $this->mahasiswaDi($desa, $lokasi);
        $this->log($mhs, ['tanggal' => self::BULAN.'-09', 'nama_kepala_keluarga' => '=HYPERLINK("x")', 'jumlah_anggota_keluarga' => null]);
        $this->log($mhs, ['tanggal' => self::BULAN.'-20']);

        foreach ([false, true] as $pemda) {
            $this->loginAs($pemda ? 'pemda' : 'admin');
            $ws = $this->xlsxFromResponse($this->get('rekapsampah/export?bulan=semua'), '/data_sampah_semua_.+\.xlsx/');
            $kolom = DataSampahExport::kolom($pemda);
            $this->assertSame(array_column($kolom, 'label'), $this->headerRow($ws));

            $data = $this->dataRows($ws);
            $iNama = array_search('nama_kepala_keluarga', array_column($kolom, 'data'));
            $log = array_values(array_filter($data, fn ($r) => $r[$iNama] === '=HYPERLINK("x")'));
            $this->assertCount(1, $log, 'teks formula tetap teks');
            $log = $log[0];
            if ($pemda) {
                $this->assertSame('September 2026', $log[0]);
                $this->assertSame('Minggu 2 (8–14)', $log[1]);
            } else {
                $this->assertNotSame('September 2026', $log[0]);
            }
            // Jiwa null → "-" untuk Jumlah Jiwa & Penurunan Timbulan
            $this->assertSame('-', $log[array_search('jml_jiwa', array_column($kolom, 'data'))]);
            $this->assertSame('-', $log[count($kolom) - 1]);

            $labels = array_column($data, 0);
            $this->assertNotEmpty(array_filter($labels, fn ($v) => str_contains((string) $v, 'Kota Export')));
            $this->assertContains('Subtotal September 2026', $labels);
            $this->assertContains('Total Kota Export', $labels);
            $this->assertSame($pemda, in_array('Subtotal Minggu 2 (8–14)', $labels, true));
        }
    }

    public function test_edit_koefisien_lokasi_program_menginvalidasi_cache_rekap(): void
    {
        $lokasi = LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Cache', 'koefisien_timbulan_sampah' => 0.5]);
        // 0.5 × 4 = 2, N 1 → 50%
        $this->log($this->mahasiswaDi(Desa::factory()->create(), $lokasi), ['organik_kg' => 0.5, 'anorganik_kg' => 0.25, 'residu_kg' => 0.25]);
        $this->loginAs('admin');
        $sub = fn () => $this->get('rekapsampah?bulan=semua')->assertOk()->viewData('subtotal')->first();

        $this->assertEquals(50.0, $sub()->total->persen_penurunan_timbulan);

        // Ubah langsung di DB (tanpa controller) → masih cache lama, membuktikan cache aktif
        DB::table('lokasi_program')->where('id', $lokasi->id)->update(['koefisien_timbulan_sampah' => 1]);
        $this->assertEquals(0.5, $sub()->koefisien);

        $this->put('lokasiprogram/update', ['id' => $lokasi->id, 'nama_lokasi' => 'Kota Cache', 'koefisien_timbulan_sampah' => '0.25'])
            ->assertJson(['success' => true]);
        $baru = $sub();
        $this->assertEquals(0.25, $baru->koefisien);
        // 0.25 × 4 = 1, N 1 → 0%
        $this->assertEquals(0.0, $baru->total->persen_penurunan_timbulan);

        $this->put('lokasiprogram/update', ['id' => $lokasi->id, 'nama_lokasi' => 'Kota Cache Baru', 'koefisien_timbulan_sampah' => '0.25'])
            ->assertJson(['success' => true]);
        $this->assertSame('Kota Cache Baru', $sub()->nama);
        $this->assertContains('Kota Cache Baru', $this->get('rekapsampah')->viewData('lokasiList')->pluck('nama_lokasi')->all());
    }
}
