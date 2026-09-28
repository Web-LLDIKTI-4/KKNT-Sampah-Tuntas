<?php

namespace Tests\Feature\Master;

use App\Models\LokasiProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LokasiProgramTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_lokasi_with_image(): void
    {
        Storage::fake('public');
        $this->loginAs('admin');

        $this->put('lokasiprogram/insert', [
            'nama_lokasi' => 'Kota Sukabumi',
            'gambar' => UploadedFile::fake()->image('lokasi.jpg'),
        ])->assertJson(['success' => true]);

        $lokasi = LokasiProgram::where('nama_lokasi', 'Kota Sukabumi')->firstOrFail();
        Storage::disk('public')->assertExists($lokasi->gambar);
    }

    public function test_non_image_upload_is_rejected(): void
    {
        Storage::fake('public');
        $this->loginAs('admin');

        $this->put('lokasiprogram/insert', [
            'nama_lokasi' => 'Kota Bogor',
            'gambar' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
        ])->assertJson(['success' => false])->assertJsonValidationErrors('gambar', 'errors');
    }

    public function test_update_replaces_old_image(): void
    {
        Storage::fake('public');
        $this->loginAs('admin');
        $lama = UploadedFile::fake()->image('lama.jpg')->store('lokasi', 'public');
        $lokasi = LokasiProgram::create(['nama_lokasi' => 'Kota Depok', 'gambar' => $lama]);

        $this->put('lokasiprogram/update', [
            'id' => $lokasi->id,
            'nama_lokasi' => 'Kota Depok',
            'gambar' => UploadedFile::fake()->image('baru.png'),
        ])->assertJson(['success' => true]);

        Storage::disk('public')->assertMissing($lama);
        Storage::disk('public')->assertExists($lokasi->fresh()->gambar);
    }

    public function test_lokasi_in_use_cannot_be_deleted(): void
    {
        $admin = $this->loginAs('admin');

        $this->put('lokasiprogram/destroy', ['id' => $admin->location_program])->assertJson(['success' => false]);
        $this->assertDatabaseHas('lokasi_program', ['id' => $admin->location_program]);
    }
}
