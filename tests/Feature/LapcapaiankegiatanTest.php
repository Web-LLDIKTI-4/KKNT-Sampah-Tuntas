<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\KategoriKegiatan;
use App\Models\CapaianKegiatan;
use App\Models\Mahasiswa;
use App\Models\Pjdesa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class LapcapaiankegiatanTest extends TestCase
{
    use RefreshDatabase;

    private function capaian(string $namaKetua, string $namaKategori): void
    {
        $mhs = Mahasiswa::factory()->create(['nama' => $namaKetua]);
        Pjdesa::create(['email' => $mhs->email, 'id_desa' => Desa::factory()->create()->id_desa]);
        CapaianKegiatan::factory()->create([
            'email' => $mhs->email,
            'id_kategori' => KategoriKegiatan::factory()->create(['nama_kategori' => $namaKategori])->id_kategori,
        ]);
    }

    private function datatable(string $query): TestResponse
    {
        $columns = '';
        foreach (['nama_kategori', 'pjdesa'] as $i => $kolom) {
            $columns .= "&columns[$i][data]=$kolom&columns[$i][name]=$kolom&columns[$i][searchable]=true&columns[$i][orderable]=true";
        }

        return $this->getJson('lapcapaiankegiatan/listdataserver?draw=1&start=0&length=10'.$columns.$query, ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();
    }

    public function test_search_and_order_on_kategori_and_ketua_columns(): void
    {
        $this->loginAs('admin');
        $this->capaian('Andi Ketua', 'Bank Sampah');
        $this->capaian('Zaki Ketua', 'Komposter');

        $this->datatable('&search[value]=Kompos')->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.nama_kategori', 'Komposter');
        $this->datatable('&search[value]=Andi')->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.pjdesa', 'Andi Ketua');

        $this->datatable('&order[0][column]=0&order[0][dir]=desc')->assertJsonPath('data.0.nama_kategori', 'Komposter');
        $this->datatable('&order[0][column]=1&order[0][dir]=asc')->assertJsonPath('data.0.pjdesa', 'Andi Ketua');
    }

    public function test_listdata_has_bulan_label_and_defaults_to_latest_month(): void
    {
        $this->loginAs('admin');
        CapaianKegiatan::factory()->create(['email' => 'lama@pps.test', 'bulan' => '2026-08-01']);
        CapaianKegiatan::factory()->create(['email' => 'baru@pps.test', 'bulan' => '2026-09-01']);

        $data = $this->getJson('lapcapaiankegiatan/listdataserver?draw=1&start=0&length=10', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('data');

        $this->assertSame(['baru@pps.test', 'lama@pps.test'], array_column($data, 'email'));
        $this->assertSame(Carbon::parse('2026-09-01')->translatedFormat('F Y'), $data[0]['bulan_label']);
    }
}
