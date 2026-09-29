<?php

namespace Tests\Feature\Profile;

use App\Models\Desa;
use App\Models\Mahasiswa_lokasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_photo_is_stored_privately_with_random_name(): void
    {
        Storage::fake('local');
        $user = $this->loginAs('dpl');

        $this->put('profile/prosesuploadpoto', ['file_upload' => UploadedFile::fake()->image('../../evil<script>.png')])
            ->assertJson(['success' => true]);

        $image = $user->fresh()->image;
        $this->assertStringNotContainsString('evil', $image);
        Storage::disk('local')->assertExists('photo/'.$image);
        $this->get('profile/getPoto')->assertOk();
    }

    public function test_replacing_photo_deletes_old_file(): void
    {
        Storage::fake('local');
        $user = $this->loginAs('mahasiswa');

        $this->put('mhsprofile/prosesuploadpoto', ['file_upload' => UploadedFile::fake()->image('a.png')]);
        $lama = $user->fresh()->image;
        $this->put('mhsprofile/prosesuploadpoto', ['file_upload' => UploadedFile::fake()->image('b.jpg')]);

        Storage::disk('local')->assertMissing('photo/'.$lama);
        Storage::disk('local')->assertExists('photo/'.$user->fresh()->image);
    }

    public function test_non_image_upload_is_rejected(): void
    {
        Storage::fake('local');
        $this->loginAs('dpl');

        $this->put('profile/prosesuploadpoto', ['file_upload' => UploadedFile::fake()->create('shell.php', 1, 'application/x-php')])
            ->assertJson(['success' => false, 'message' => 'Foto gagal diunggah']);
    }

    public function test_dpl_profile_update_creates_dpl_record(): void
    {
        $user = $this->loginAs('dpl');

        $this->put('profile/update', ['nama' => 'Dr. Budi', 'nidn' => '0412345678', 'prodi' => 'TI', 'phone' => '0812-3456'])
            ->assertJson(['success' => true]);

        $this->assertSame('Dr. Budi', $user->fresh()->name);
        $this->assertDatabaseHas('dpl', ['email' => $user->email, 'nidn' => '0412345678']);
        $this->get('profile/data')->assertOk();
    }

    public function test_mahasiswa_profile_and_lokasi(): void
    {
        $user = $this->loginAs('mahasiswa');
        $desa = Desa::factory()->create();

        $this->put('mhsprofile/update', ['nama' => 'Ani', 'nim' => '123', 'prodi' => 'SI', 'tahun_masuk' => 2022, 'phone' => '<script>'])
            ->assertJsonValidationErrors('phone', 'errors');
        $this->put('mhsprofile/setlokasi', ['id_desa' => 'bukan-uuid', 'tahun' => date('Y')])
            ->assertJsonValidationErrors('id_desa', 'errors');
        $this->put('mhsprofile/setlokasi', ['id_desa' => $desa->id_desa, 'tahun' => date('Y')])
            ->assertJson(['success' => true, 'message' => 'Lokasi berhasil diperbarui']);

        $this->assertSame($desa->id_desa, Mahasiswa_lokasi::where('id_mahasiswa', $user->mahasiswa->id_mahasiswa)->value('id_desa'));
        $this->get('mhsprofile/data')->assertOk();
    }

    public function test_dpl_cannot_set_mahasiswa_lokasi(): void
    {
        $this->loginAs('dpl');

        $this->put('mhsprofile/setlokasi', ['id_desa' => Desa::factory()->create()->id_desa, 'tahun' => date('Y')])
            ->assertForbidden();
    }
}
