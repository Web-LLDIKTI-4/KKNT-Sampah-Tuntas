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

    public function test_listdataserver_malformed_order_does_not_500(): void
    {
        $this->loginAs('admin');
        CapaianKegiatan::factory()->create(['email' => 'lama@pps.test', 'bulan' => '2026-08-01']);
        CapaianKegiatan::factory()->create(['email' => 'baru@pps.test', 'bulan' => '2026-09-01']);

        // >50 columns: extras are dropped, order pointing at a dropped column is ignored
        $many = '&'.implode('&', array_map(fn ($i) => "columns[$i][data]=x$i", range(0, 59))).'&order[0][column]=55';

        // Order rusak dibuang; urutan default (bulan terbaru) tetap berlaku
        foreach ([
            '&order=x',
            '&order[0][dir][]=x',
            '&order[0][column]=1&columns[1]=x',
            '&columns=x',
            '&columns[0][search]=x',
            '&columns[0][data][]=x&columns[0][searchable]=true',
            '&columns[0][data]=nama&columns[0][searchable]=true&columns[0][search][value][]=x',
            '&columns[x][data]=nama&order[0][column]=x',
            '&columns[0][data]=a;b&columns[0][name]=a;b&columns[0][orderable]=true&order[0][column]=0&order[0][dir]=asc',
            '&search=x',
            '&search[value][]=x',
            '&columns[-1][data]=bulan&columns[-1][orderable]=true&order[0][column]=-1&order[0][dir]=asc',
            '&columns[99999][data]=bulan&columns[99999][orderable]=true&order[0][column]=99999',
            '&columns[99999999999999999999][data]=bulan&order[0][column]=99999999999999999999',
            '&columns[7][data]=bulan&columns[7][orderable]=true&columns[3][data]=bulan&order[0][column]=7&order[0][dir]=desc',
            '&columns[0][search][regex]=true&search[regex]=true',
            '&columns[0][data]=bulan%0A&columns[0][name]=bulan%0A&columns[0][orderable]=true&order[0][column]=0',
            '&searchPanes[bulan][]=x',
            $many,
        ] as $order) {
            $this->datatable($order)->assertJsonCount(2, 'data')->assertJsonPath('data.0.email', 'baru@pps.test');
        }

        // Sort/search on users.password via relation is dropped by the whitelist; default order still applies
        foreach ([
            '&columns[2][data]=user.password&columns[2][name]=user.password&columns[2][searchable]=true&columns[2][orderable]=true&columns[2][search][value]=%242y&order[0][column]=2&order[0][dir]=asc',
            '&columns[2][data]=pjdesa.mahasiswa.user.password&columns[2][name]=pjdesa.mahasiswa.user.password&columns[2][searchable]=true&columns[2][orderable]=true&columns[2][search][value]=%242y&order[0][column]=2&order[0][dir]=asc',
        ] as $order) {
            $this->datatable($order)->assertJsonCount(2, 'data')->assertJsonPath('data.0.email', 'baru@pps.test');
        }

        // 2500-term search is capped to 100 chars / 10 terms
        $start = microtime(true);
        $this->datatable('&columns[0][search][value]='.str_repeat('a+', 2500).'&search[value]='.str_repeat('a+', 2500));
        $this->assertLessThan(2, microtime(true) - $start);

        // regex=true is forced off: '[' becomes a LIKE match instead of a REGEXP error
        $this->datatable('&columns[2][data]=permasalahan&columns[2][name]=permasalahan&columns[2][searchable]=true&columns[2][search][value]=%5B&columns[2][search][regex]=true')
            ->assertJsonCount(0, 'data');
    }
}
