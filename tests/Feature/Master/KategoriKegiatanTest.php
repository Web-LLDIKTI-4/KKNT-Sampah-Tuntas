<?php

namespace Tests\Feature\Master;

use App\Models\KategoriKegiatan;
use App\Models\CapaianKegiatan;
use App\Models\Logkegiatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class KategoriKegiatanTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_kategori_kegiatan(): void
    {
        $this->loginAs('admin');

        $this->put('kategori-kegiatan/insert', ['nama_kategori' => 'Digitalisasi UMKM'])->assertJson(['success' => true]);
        $this->put('kategori-kegiatan/insert', ['nama_kategori' => 'Digitalisasi UMKM'])
            ->assertJsonPath('errors.nama_kategori.0', 'Nama aktivitas sudah ada!');

        $kategori = KategoriKegiatan::where('nama_kategori', 'Digitalisasi UMKM')->firstOrFail();
        $this->put('kategori-kegiatan/update', ['id_kategori' => $kategori->id_kategori, 'nama_kategori' => 'Digitalisasi Desa'])->assertJson(['success' => true]);
        $this->put('kategori-kegiatan/destroy', ['id_kategori' => $kategori->id_kategori])->assertJson(['success' => true]);
        $this->assertDatabaseMissing('kategori_kegiatan', ['id_kategori' => $kategori->id_kategori]);
    }

    public function test_kategori_with_capaian_cannot_be_deleted(): void
    {
        $this->loginAs('admin');
        $capaian = CapaianKegiatan::factory()->create(['id_kategori' => KategoriKegiatan::factory()->create()->id_kategori]);

        $this->put('kategori-kegiatan/destroy', ['id_kategori' => $capaian->id_kategori])->assertJson(['success' => false]);
    }

    public function test_kategori_with_logkegiatan_cannot_be_deleted(): void
    {
        $this->loginAs('admin');
        $kategori = KategoriKegiatan::factory()->create();
        Logkegiatan::factory()->create(['id_kategori' => $kategori->id_kategori]);

        $this->put('kategori-kegiatan/destroy', ['id_kategori' => $kategori->id_kategori])->assertJson(['success' => false]);
        $this->assertModelExists($kategori);
    }

    public function test_exports_download(): void
    {
        Excel::fake();
        $this->loginAs('admin');

        $this->get('kategori-kegiatan/export')->assertOk();
    }
}
