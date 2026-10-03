<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

use App\Models\Panduan;
use Database\Seeders\PanduanSeeder;

class PanduanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(Panduan::DISK);
    }

    private function validPayload(array $overrides = []): array
    {
        return $overrides + [
            'judul' => 'Panduan Pengisian Logbook',
            'deskripsi' => 'Langkah mengisi logbook harian.',
            'is_aktif' => '1',
            'file' => UploadedFile::fake()->create('panduan-logbook.pdf', 120, 'application/pdf'),
        ];
    }

    private function createPanduanWithFile(array $attributes = []): Panduan
    {
        $filePath = UploadedFile::fake()->create('lama.pdf', 50, 'application/pdf')->store('panduan', Panduan::DISK);

        return Panduan::factory()->create($attributes + ['file_path' => $filePath, 'nama_file' => 'lama.pdf']);
    }

    public function test_admin_can_open_index_and_list(): void
    {
        $this->loginAs('admin');
        $this->createPanduanWithFile(['judul' => 'Panduan <b>Upload</b>']);

        $this->get('panduan')->assertOk();
        $this->get('panduan/listdata')->assertOk()->assertSee(route('panduan.listdataserver'), false);
        $this->get('panduan/tambah')->assertOk()->assertSee('enctype="multipart/form-data"', false);

        $this->getJson('panduan/listdataserver', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertSee('Panduan &lt;b&gt;Upload&lt;', false)
            ->assertJsonMissingPath('data.0.file_path')
            ->assertJsonMissingPath('data.0.peruntukan');
    }

    public function test_admin_can_upload_pdf(): void
    {
        $admin = $this->loginAs('admin');

        $this->put('panduan/insert', $this->validPayload())->assertJson(['success' => true]);

        $panduan = Panduan::where('judul', 'Panduan Pengisian Logbook')->firstOrFail();
        Storage::disk(Panduan::DISK)->assertExists($panduan->file_path);
        $this->assertStringStartsWith('panduan/', $panduan->file_path);
        $this->assertSame('panduan-logbook.pdf', $panduan->nama_file);
        $this->assertSame($admin->id, $panduan->uploaded_by);
        $this->assertTrue($panduan->is_aktif);
    }

    public function test_insert_without_peruntukan_defaults_to_semua(): void
    {
        $this->loginAs('admin');

        $this->put('panduan/insert', $this->validPayload(['peruntukan' => 'mahasiswa']))->assertJson(['success' => true]);

        // Input peruntukan diabaikan; kolom DB memakai default
        $this->assertDatabaseHas('panduan', ['judul' => 'Panduan Pengisian Logbook', 'peruntukan' => 'semua']);
    }

    public function test_dangerous_or_missing_files_are_rejected(): void
    {
        $this->loginAs('admin');
        $rejectedFiles = [
            'php' => UploadedFile::fake()->create('shell.php', 5, 'application/x-php'),
            'image' => UploadedFile::fake()->image('foto.png'),
            'html' => UploadedFile::fake()->create('xss.html', 5, 'text/html'),
            'too large' => UploadedFile::fake()->create('besar.pdf', 10241, 'application/pdf'),
            'missing' => null,
        ];

        foreach ($rejectedFiles as $case => $file) {
            $this->put('panduan/insert', $this->validPayload(['file' => $file]))
                ->assertJson(['success' => false])
                ->assertJsonValidationErrors('file', 'errors');
        }

        $this->assertDatabaseCount('panduan', 0);
    }

    public function test_upload_exceeding_php_ini_limit_shows_clear_message(): void
    {
        $this->loginAs('admin');
        $panduan = $this->createPanduanWithFile();
        $failedUpload = new UploadedFile(__FILE__, 'besar.pdf', 'application/pdf', UPLOAD_ERR_INI_SIZE, true);

        $this->put('panduan/insert', $this->validPayload(['file' => $failedUpload]))
            ->assertJson(['success' => false])
            ->assertJsonPath('errors.file.0', fn (string $message) => str_contains($message, 'batas upload server'));

        // Update dengan file gagal tidak boleh lolos diam-diam sebagai "tanpa ganti file"
        $this->put('panduan/update', $this->validPayload(['id_panduan' => $panduan->id_panduan, 'file' => $failedUpload]))
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('file', 'errors');
    }

    public function test_request_exceeding_post_max_size_returns_json_413(): void
    {
        $this->loginAs('admin');

        $this->call('PUT', 'panduan/insert', [], [], [], [
            'CONTENT_LENGTH' => (string) (1024 * 1024 * 1024),
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_ACCEPT' => 'application/json',
        ])->assertStatus(413)->assertJson(['success' => false]);
    }

    public function test_update_with_new_file_replaces_old_file(): void
    {
        $this->loginAs('admin');
        $panduan = $this->createPanduanWithFile();
        $oldFilePath = $panduan->file_path;

        $this->put('panduan/update', $this->validPayload([
            'id_panduan' => $panduan->id_panduan,
            'judul' => 'Panduan Baru',
            'file' => UploadedFile::fake()->create('baru.docx', 30, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ]))->assertJson(['success' => true]);

        $panduan->refresh();
        Storage::disk(Panduan::DISK)->assertMissing($oldFilePath);
        Storage::disk(Panduan::DISK)->assertExists($panduan->file_path);
        $this->assertSame('baru.docx', $panduan->nama_file);
        $this->assertSame('Panduan Baru', $panduan->judul);
    }

    public function test_update_without_file_keeps_existing_file(): void
    {
        $this->loginAs('admin');
        $panduan = $this->createPanduanWithFile();

        $this->put('panduan/update', $this->validPayload([
            'id_panduan' => $panduan->id_panduan,
            'is_aktif' => '0',
            'file' => null,
        ]))->assertJson(['success' => true]);

        $updated = $panduan->fresh();
        $this->assertSame($panduan->file_path, $updated->file_path);
        $this->assertFalse($updated->is_aktif);
        Storage::disk(Panduan::DISK)->assertExists($updated->file_path);
    }

    public function test_destroy_removes_row_and_file(): void
    {
        $this->loginAs('admin');
        $panduan = $this->createPanduanWithFile();

        $this->put('panduan/destroy', ['id_panduan' => $panduan->id_panduan])->assertJson(['success' => true]);

        $this->assertDatabaseMissing('panduan', ['id_panduan' => $panduan->id_panduan]);
        Storage::disk(Panduan::DISK)->assertMissing($panduan->file_path);
        $this->put('panduan/destroy', ['id_panduan' => $panduan->id_panduan])->assertNotFound();
    }

    public function test_array_id_is_rejected_without_server_error(): void
    {
        $this->loginAs('admin');
        $panduan = $this->createPanduanWithFile();
        $arrayId = ['id_panduan' => [$panduan->id_panduan]];

        $this->put('panduan/destroy', $arrayId)->assertNotFound()->assertJson(['success' => false]);
        $this->put('panduan/destroy', ['id_panduan' => 'bukan-uuid'])->assertNotFound()->assertJson(['success' => false]);
        $this->put('panduan/update', $this->validPayload($arrayId + ['file' => null]))
            ->assertOk()
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('id_panduan', 'errors');

        $this->assertDatabaseHas('panduan', ['id_panduan' => $panduan->id_panduan]);
    }

    public function test_control_characters_are_stripped_from_file_name(): void
    {
        $this->loginAs('admin');

        $this->put('panduan/insert', $this->validPayload([
            'file' => UploadedFile::fake()->create("a\r\nSet-Cookie: x=1\t.pdf", 10, 'application/pdf'),
        ]))->assertJson(['success' => true]);

        $this->assertSame('aSet-Cookie: x=1.pdf', Panduan::firstOrFail()->nama_file);
    }

    public function test_download_returns_file_or_404_when_missing(): void
    {
        $this->loginAs('admin');
        $panduan = $this->createPanduanWithFile();

        $this->get(route('panduan.download', $panduan->id_panduan))
            ->assertOk()
            ->assertDownload('lama.pdf');

        Storage::disk(Panduan::DISK)->delete($panduan->file_path);
        $this->get(route('panduan.download', $panduan->id_panduan))->assertNotFound();
    }

    public function test_edit_form_shows_current_file(): void
    {
        $this->loginAs('admin');
        $panduan = $this->createPanduanWithFile();

        $this->get('panduan/edit/'.$panduan->id_panduan)
            ->assertOk()
            ->assertSee('name="id_panduan"', false)
            ->assertSee(route('panduan.download', $panduan->id_panduan), false)
            ->assertSee('tampil publik', false)
            ->assertDontSee('name="peruntukan"', false);
    }

    public function test_admin_can_download_inactive_panduan(): void
    {
        $this->loginAs('admin');
        $panduan = $this->createPanduanWithFile(['is_aktif' => false]);

        $this->get(route('panduan.download', $panduan->id_panduan))->assertOk();
    }

    public function test_non_admin_roles_are_redirected(): void
    {
        $panduan = $this->createPanduanWithFile();

        foreach (['mahasiswa', 'dpl', 'pt'] as $role) {
            $this->loginAs($role);
            $this->get('panduan')->assertRedirect(route('home'));
            $this->get(route('panduan.download', $panduan->id_panduan))->assertRedirect(route('home'));
        }
    }

    public function test_kepala_cannot_write_or_download(): void
    {
        $this->loginAs('kepala');
        $panduan = $this->createPanduanWithFile();

        $this->put('panduan/insert', $this->validPayload())->assertForbidden();
        $this->get(route('panduan.download', $panduan->id_panduan))->assertRedirect(route('home'));
    }

    public function test_seeder_creates_100_rows_with_own_files(): void
    {
        $this->seed(PanduanSeeder::class);

        $filePaths = Panduan::pluck('file_path');
        $this->assertCount(100, $filePaths);
        $this->assertCount(100, $filePaths->unique());
        $this->assertCount(100, Storage::disk(Panduan::DISK)->files('panduan/dummy'));
    }
}
