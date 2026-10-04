<?php

namespace Tests\Feature\Master;

use App\Models\Desa;
use App\Models\Desaprofile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesaprofileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_html_is_sanitized_on_save(): void
    {
        $this->loginAs('admin');
        $desa = Desa::factory()->create();

        $this->put('desaprofile/insert', [
            'id_desa' => $desa->id_desa,
            'tahun' => date('Y'),
            'potensi' => '<p onclick="x()">Wisata <b>alam</b></p><script>alert(1)</script>',
            'masalah' => '<img src=x onerror=alert(1)>Sampah',
        ])->assertJson(['success' => true]);

        $profile = Desaprofile::firstOrFail();
        $this->assertSame('<p>Wisata <b>alam</b></p>', $profile->potensi);
        $this->assertSame('Sampah', $profile->masalah);
    }

    public function test_listdata_returns_sanitized_html_without_double_escape(): void
    {
        $this->loginAs('admin');
        // Data lama yang tersimpan mentah (sebelum sanitasi saat simpan) tetap disanitasi saat tampil
        Desaprofile::factory()->create([
            'potensi' => '<p onclick="x()">Wisata <b>alam</b></p><script>alert(1)</script>',
            'masalah' => '<img src=x onerror=alert(1)>Sampah',
        ]);

        $row = $this->getJson('desaprofile/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('data.0');

        $this->assertSame('<p>Wisata <b>alam</b></p>', $row['potensi']);
        $this->assertSame('Sampah', $row['masalah']);
        $this->assertStringNotContainsString('&lt;', $row['potensi']);
        $this->assertStringNotContainsString('<script', $row['potensi']);
    }

    public function test_required_fields_and_update(): void
    {
        $this->loginAs('admin');
        $profile = Desaprofile::factory()->create();

        $this->put('desaprofile/insert', ['id_desa' => $profile->id_desa, 'tahun' => date('Y')])
            ->assertJsonPath('errors.potensi.0', 'Potensi desa harus diisi.')
            ->assertJsonPath('errors.masalah.0', 'Masalah desa harus diisi.');

        $this->put('desaprofile/update', [
            'id_profile' => $profile->id_profile,
            'id_desa' => $profile->id_desa,
            'tahun' => date('Y'),
            'potensi' => 'Baru',
            'masalah' => 'Baru juga',
        ])->assertJson(['success' => true]);
        $this->assertSame('Baru', $profile->fresh()->potensi);
    }

    public function test_admin_can_delete_profile(): void
    {
        $this->loginAs('admin');
        $profile = Desaprofile::factory()->create();

        $this->put('desaprofile/destroy', ['id_profile' => $profile->id_profile])->assertJson(['success' => true]);
        $this->assertDatabaseMissing('desa_profile', ['id_profile' => $profile->id_profile]);
    }
}
