<?php

namespace Tests\Feature\Admin;

use App\Models\Desa;
use App\Models\Dpl;
use App\Models\Dplmentoring;
use App\Models\CapaianKegiatan;
use App\Models\Logkegiatan;
use App\Models\LokasiProgram;
use App\Models\Mahasiswa;
use App\Models\Nilaikonversi;
use App\Models\Pjdesa;
use App\Models\Tugasakhir;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class PersonMasterTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_mahasiswa_removes_all_related_data(): void
    {
        $this->loginAs('admin');
        $mhs = Mahasiswa::factory()->create();
        User::factory()->role('mahasiswa')->create(['email' => $mhs->email]);
        Logkegiatan::factory()->create(['email' => $mhs->email]);
        CapaianKegiatan::factory()->create(['email' => $mhs->email]);
        Nilaikonversi::factory()->create(['id_mahasiswa' => $mhs->id_mahasiswa]);
        Dplmentoring::create(['email_mahasiswa' => $mhs->email, 'email_dpl' => 'dpl@pps.test']);

        $this->put('mahasiswa/destroy', ['id_mahasiswa' => $mhs->id_mahasiswa])->assertJson(['success' => true]);

        foreach (['mahasiswa' => 'email', 'logkegiatan' => 'email', 'capaian_kegiatan' => 'email', 'users' => 'email'] as $table => $col) {
            $this->assertDatabaseMissing($table, [$col => $mhs->email]);
        }
        $this->assertDatabaseMissing('nilai_konversi', ['id_mahasiswa' => $mhs->id_mahasiswa]);
        $this->assertDatabaseMissing('dpl_mentoring', ['email_mahasiswa' => $mhs->email]);
    }

    public function test_deleting_dpl_keeps_student_final_assignment(): void
    {
        $this->loginAs('admin');
        $dpl = Dpl::factory()->create();
        $ta = Tugasakhir::factory()->create(['email' => 'mhs@pps.test', 'email_dpl' => $dpl->email, 'nilai_dpl' => 88]);

        $this->put('dpl/destroy', ['id_dpl' => $dpl->id_dpl])->assertJson(['success' => true]);

        $this->assertDatabaseMissing('dpl', ['id_dpl' => $dpl->id_dpl]);
        $this->assertNull($ta->fresh()->email_dpl);
        $this->assertSame(88, $ta->fresh()->nilai_dpl);
    }

    public function test_import_rejects_non_spreadsheet_files(): void
    {
        $this->loginAs('admin');

        $this->put('mahasiswa/prosesimport', ['file' => UploadedFile::fake()->create('x.php', 5, 'text/x-php')])
            ->assertJsonValidationErrors('file', 'errors');
    }

    public function test_import_returns_json_with_skipped_rows(): void
    {
        $this->loginAs('admin');
        $lokasi = LokasiProgram::factory()->create();
        $rows = [
            ['NIM', 'Tahun', 'Nama', 'Email', 'Prodi', 'Kode PT', 'HP', 'Lokasi'],
            ['1001', '2023', 'Ani', 'ani@pps.test', 'TI', '041001', '0811', $lokasi->nama_lokasi],
            ['1002', '2023', 'Budi', 'budi@pps.test', 'TI', '041001', '0812', 'Lokasi Tidak Ada'],
        ];

        $this->put('mahasiswa/prosesimport', ['file' => $this->xlsx($rows)])
            ->assertJson(['success' => true, 'toast' => 'warning', 'message' => '1 data berhasil diimpor, 1 baris dilewati.'])
            ->assertJsonPath('import_errors.0', 'Baris NIM 1002: Lokasi Program "Lokasi Tidak Ada" tidak ditemukan.');

        $this->assertDatabaseHas('mahasiswa', ['nim' => '1001']);
        $this->assertDatabaseMissing('mahasiswa', ['nim' => '1002']);
    }

    public function test_dpl_import_with_no_valid_rows_uses_error_toast(): void
    {
        $this->loginAs('admin');
        $rows = [
            ['NIDN', 'Nama', 'Email', 'Lokasi', 'Prodi', 'Kode PT', 'HP'],
            ['19820219', 'Dosen A', 'a@pps.test', 'Karawang', 'TI', '041001', '0811'],
            ['19820218', 'Dosen B', 'b@pps.test', 'Tanjung Pinang', 'TI', '041001', '0812'],
        ];

        $this->put('dpl/prosesimport', ['file' => $this->xlsx($rows)])
            ->assertJson(['toast' => 'error', 'message' => 'Tidak ada data yang diimpor, 2 baris dilewati.'])
            ->assertJsonCount(2, 'import_errors');

        $this->assertDatabaseCount('dpl', 0);
    }

    public function test_dpl_import_names_duplicate_columns(): void
    {
        $this->loginAs('admin');
        $lokasi = LokasiProgram::factory()->create();
        Dpl::factory()->create(['nidn' => '111', 'kodept' => '041001', 'email' => 'lama@pps.test', 'phone' => '08111']);
        $rows = [
            ['NIDN', 'Nama', 'Email', 'Lokasi', 'Prodi', 'Kode PT', 'HP'],
            ['111', 'Dosen A', 'baru@pps.test', $lokasi->nama_lokasi, 'TI', '041001', '08111'],
            ['222', 'Dosen B', 'b@pps.test', $lokasi->nama_lokasi, 'TI', '041001', '08222'],
            ['333', 'Dosen C', 'b@pps.test', $lokasi->nama_lokasi, 'TI', '041001', '08333'],
        ];

        $this->put('dpl/prosesimport', ['file' => $this->xlsx($rows)])
            ->assertJsonPath('import_errors', [
                'Baris NIDN 111: NIDN 111, No HP 08111 sudah terdaftar, dilewati.',
                'Baris NIDN 333: Email b@pps.test sudah terdaftar, dilewati.',
            ]);

        $this->assertDatabaseHas('dpl', ['nidn' => '222']);
    }

    public function test_mahasiswa_import_names_duplicate_columns(): void
    {
        $this->loginAs('admin');
        $lokasi = LokasiProgram::factory()->create();
        Mahasiswa::factory()->create(['nim' => '1001', 'kodept' => '041001', 'email' => 'ani@pps.test', 'phone' => '08111']);
        $rows = [
            ['NIM', 'Tahun', 'Nama', 'Email', 'Prodi', 'Kode PT', 'HP', 'Lokasi'],
            ['1002', '2023', 'Ani', 'ani@pps.test', 'TI', '041001', '08111', $lokasi->nama_lokasi],
        ];

        $this->put('mahasiswa/prosesimport', ['file' => $this->xlsx($rows)])
            ->assertJsonPath('import_errors.0', 'Baris NIM 1002: Email ani@pps.test, No HP 08111 sudah terdaftar, dilewati.');
    }

    public function test_import_all_rows_valid_returns_success_without_errors(): void
    {
        $this->loginAs('admin');
        $lokasi = LokasiProgram::factory()->create();
        $rows = [
            ['NIM', 'Tahun', 'Nama', 'Email', 'Prodi', 'Kode PT', 'HP', 'Lokasi'],
            ['2001', '2023', 'Cici', 'cici@pps.test', 'SI', '041001', '0813', $lokasi->nama_lokasi],
        ];

        $this->put('mahasiswa/prosesimport', ['file' => $this->xlsx($rows)])
            ->assertJson(['success' => true])
            ->assertJsonMissingPath('toast')
            ->assertJsonMissingPath('import_errors');
    }

    private function xlsx(array $rows): UploadedFile
    {
        $content = Excel::raw(new class($rows) implements FromArray
        {
            public function __construct(private array $rows) {}

            public function array(): array
            {
                return $this->rows;
            }
        }, ExcelFormat::XLSX);

        return UploadedFile::fake()->createWithContent('import.xlsx', $content);
    }

    public function test_listing_flags_ketua_kelompok_for_admin_and_pt(): void
    {
        $ketua = Mahasiswa::factory()->create(['kodept' => '041996']);
        $anggota = Mahasiswa::factory()->create(['kodept' => '041996']);
        Pjdesa::create(['email' => $ketua->email, 'id_desa' => Desa::factory()->create()->id_desa]);
        $ajax = ['X-Requested-With' => 'XMLHttpRequest'];

        $this->loginAs('admin');
        $rows = collect($this->getJson('mahasiswa/listdataserver?draw=1&start=0&length=10', $ajax)->assertOk()->json('data'))->keyBy('email');
        $this->assertTrue($rows[$ketua->email]['ketua_kelompok']);
        $this->assertFalse($rows[$anggota->email]['ketua_kelompok']);

        $this->loginAs('pt', ['email' => '041996']);
        $rows = collect($this->getJson('ptmahasiswa/listdataserver?draw=1&start=0&length=10', $ajax)->assertOk()->json('data'))->keyBy('email');
        $this->assertTrue($rows[$ketua->email]['ketua_kelompok']);
        $this->assertFalse($rows[$anggota->email]['ketua_kelompok']);
    }

    public function test_listing_shows_pt_and_lokasi(): void
    {
        $this->loginAs('admin');
        Mahasiswa::factory()->count(2)->create();

        $this->getJson('mahasiswa/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('recordsTotal', 2)->assertJsonPath('data.0.nm_lemb', 'Belum Terdata');
    }
}
