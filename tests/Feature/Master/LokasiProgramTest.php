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

    public function test_koefisien_saved_on_insert_and_update(): void
    {
        $this->loginAs('admin');

        $this->put('lokasiprogram/insert', ['nama_lokasi' => 'Kota Cimahi', 'koefisien_timbulan_sampah' => '0.7525'])
            ->assertJson(['success' => true]);
        $lokasi = LokasiProgram::where('nama_lokasi', 'Kota Cimahi')->firstOrFail();
        $this->assertSame(0.7525, $lokasi->koefisien_timbulan_sampah);

        foreach (['9999.9999', '0', '12', '1.5'] as $value) {
            $this->put('lokasiprogram/update', ['id' => $lokasi->id, 'nama_lokasi' => 'Kota Cimahi', 'koefisien_timbulan_sampah' => $value])
                ->assertJson(['success' => true]);
            $this->assertSame((float) $value, $lokasi->fresh()->koefisien_timbulan_sampah, "nilai $value");
        }
        $this->assertSame(1.5, $lokasi->fresh()->koefisien_timbulan_sampah);
    }

    public function test_koefisien_null_or_empty_is_accepted(): void
    {
        $this->loginAs('admin');

        $this->put('lokasiprogram/insert', ['nama_lokasi' => 'Kota Garut'])->assertJson(['success' => true]);
        $this->put('lokasiprogram/insert', ['nama_lokasi' => 'Kota Banjar', 'koefisien_timbulan_sampah' => ''])->assertJson(['success' => true]);
        $this->assertNull(LokasiProgram::where('nama_lokasi', 'Kota Garut')->value('koefisien_timbulan_sampah'));
        $this->assertNull(LokasiProgram::where('nama_lokasi', 'Kota Banjar')->value('koefisien_timbulan_sampah'));

        // Update ke kosong menghapus nilai lama
        $lokasi = LokasiProgram::create(['nama_lokasi' => 'Kota Tasik', 'koefisien_timbulan_sampah' => 0.5]);
        $this->put('lokasiprogram/update', ['id' => $lokasi->id, 'nama_lokasi' => 'Kota Tasik', 'koefisien_timbulan_sampah' => ''])
            ->assertJson(['success' => true]);
        $this->assertNull($lokasi->fresh()->koefisien_timbulan_sampah);
    }

    public function test_invalid_koefisien_is_rejected(): void
    {
        $this->loginAs('admin');
        $cases = [
            '-0.1' => 'Koefisien tidak boleh negatif.',
            '-5' => 'Koefisien tidak boleh negatif.',
            'abc' => 'Koefisien harus berupa angka.',
            '0,5' => 'Koefisien harus berupa angka.',
            '1.23456' => 'Maksimal 4 angka di belakang koma.',
            '10000' => 'Koefisien maksimal 9999.9999.',
        ];

        foreach ($cases as $value => $message) {
            $this->put('lokasiprogram/insert', ['nama_lokasi' => 'Kota X '.$value, 'koefisien_timbulan_sampah' => (string) $value])
                ->assertJson(['success' => false])
                ->assertJsonValidationErrors(['koefisien_timbulan_sampah' => $message], 'errors');
        }
        $this->assertDatabaseMissing('lokasi_program', ['nama_lokasi' => 'Kota X abc']);

        $lokasi = LokasiProgram::create(['nama_lokasi' => 'Kota Ciamis', 'koefisien_timbulan_sampah' => 0.5]);
        $this->put('lokasiprogram/update', ['id' => $lokasi->id, 'nama_lokasi' => 'Kota Ciamis', 'koefisien_timbulan_sampah' => '-1'])
            ->assertJsonValidationErrors(['koefisien_timbulan_sampah' => 'Koefisien tidak boleh negatif.'], 'errors');
        $this->assertSame(0.5, $lokasi->fresh()->koefisien_timbulan_sampah);
    }

    public function test_listdataserver_returns_koefisien(): void
    {
        $this->loginAs('admin');
        LokasiProgram::create(['nama_lokasi' => 'Kota Koef', 'koefisien_timbulan_sampah' => 0.25]);

        $row = collect($this->getJson('lokasiprogram/listdataserver?draw=1&start=0&length=100', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('data'))->firstWhere('nama_lokasi', 'Kota Koef');
        $this->assertNotNull($row);
        $this->assertEquals(0.25, (float) $row['koefisien_timbulan_sampah']);
    }

    public function test_non_admin_cannot_set_koefisien(): void
    {
        $lokasi = LokasiProgram::create(['nama_lokasi' => 'Kota Aman', 'koefisien_timbulan_sampah' => 0.5]);

        // Ditolak middleware role:admin (403 atau redirect ke home, tergantung role)
        foreach (['pemda', 'mahasiswa', 'pt'] as $role) {
            $this->loginAs($role);
            foreach ([
                $this->put('lokasiprogram/insert', ['nama_lokasi' => 'Kota Liar', 'koefisien_timbulan_sampah' => '1']),
                $this->put('lokasiprogram/update', ['id' => $lokasi->id, 'nama_lokasi' => 'Kota Aman', 'koefisien_timbulan_sampah' => '9']),
            ] as $response) {
                $this->assertContains($response->getStatusCode(), [302, 403], "role $role");
            }
        }
        auth()->logout();
        $this->put('lokasiprogram/insert', ['nama_lokasi' => 'Kota Liar', 'koefisien_timbulan_sampah' => '1'])->assertRedirect(route('login'));
        $this->assertDatabaseMissing('lokasi_program', ['nama_lokasi' => 'Kota Liar']);
        $this->assertSame(0.5, $lokasi->fresh()->koefisien_timbulan_sampah);
    }

    public function test_koefisien_point_four_is_cast_to_float_without_trailing_zeros(): void
    {
        $this->loginAs('admin');

        $this->put('lokasiprogram/insert', ['nama_lokasi' => 'Kota Empat', 'koefisien_timbulan_sampah' => '0.4'])
            ->assertJson(['success' => true]);
        $lokasi = LokasiProgram::where('nama_lokasi', 'Kota Empat')->firstOrFail();
        $this->assertSame(0.4, $lokasi->koefisien_timbulan_sampah);
        $this->assertSame('0.4', (string) $lokasi->koefisien_timbulan_sampah);
    }
}
