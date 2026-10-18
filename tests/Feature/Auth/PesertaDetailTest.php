<?php

namespace Tests\Feature\Auth;

use App\Models\Dpl;
use App\Models\Mahasiswa;
use App\Models\Pjdesa;
use App\Models\Satuanpendidikan;
use App\Models\User;
use App\Services\DataPesertaSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PesertaDetailTest extends TestCase
{
    use RefreshDatabase;

    private Satuanpendidikan $pt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pt = Satuanpendidikan::factory()->create();
        $other = Satuanpendidikan::factory()->create();

        Mahasiswa::factory()->create(['kodept' => $this->pt->npsn, 'nama' => 'Citra']);
        Mahasiswa::factory()->create(['kodept' => $this->pt->npsn, 'nama' => 'Andi']);
        $ketua = Mahasiswa::factory()->create(['kodept' => $this->pt->npsn, 'nama' => 'Budi']);
        Mahasiswa::factory()->create(['kodept' => $other->npsn]);

        // Two pj_desa rows for the same ketua must still count once
        Pjdesa::create(['email' => $ketua->email]);
        Pjdesa::create(['email' => $ketua->email]);

        Dpl::factory()->count(2)->create(['kodept' => $this->pt->npsn]);
        Dpl::factory()->create(['kodept' => $other->npsn]);

        User::factory()->role('pt')->create(['email' => $this->pt->npsn, 'name' => 'PIC Kampus']);
    }

    private function detail(string $jenis, ?string $kodept = null)
    {
        return $this->getJson(route('login.peserta', ['kodept' => $kodept ?? $this->pt->npsn, 'jenis' => $jenis]));
    }

    public function test_invalid_input_is_rejected(): void
    {
        $this->getJson(route('login.peserta'))->assertStatus(422)->assertJsonValidationErrors(['kodept', 'jenis']);
        $this->detail('admin')->assertStatus(422)->assertJsonValidationErrors('jenis');
        $this->detail('mahasiswa', "1' OR 1=1")->assertStatus(422)->assertJsonValidationErrors('kodept');
        $this->detail('mahasiswa', str_repeat('1', 21))->assertStatus(422)->assertJsonValidationErrors('kodept');
    }

    public function test_unknown_pt_returns_404(): void
    {
        $this->detail('mahasiswa', 'ZZZ999')->assertNotFound();
    }

    public function test_numeric_variant_of_kodept_returns_404(): void
    {
        $npsn = (string) $this->pt->npsn;

        foreach (['0'.$npsn, ltrim($npsn, '0'), $npsn.'e0'] as $variant) {
            $this->detail('mahasiswa', $variant)->assertNotFound();
        }
    }

    public function test_row_count_matches_summary(): void
    {
        $row = app(DataPesertaSummary::class)->all()->firstWhere('kodept', $this->pt->npsn);
        $map = ['mahasiswa' => 'jumlah_mahasiswa', 'dpl' => 'jumlah_dpl', 'pic' => 'jumlah_pic', 'ketua' => 'jumlah_ketua_kelompok'];

        foreach ($map as $jenis => $field) {
            $this->detail($jenis)->assertOk()
                ->assertJsonPath('nama_pt', $this->pt->nm_lemb)
                ->assertJsonPath('jenis', $jenis)
                ->assertJsonCount($row->{$field}, 'data');
        }

        $this->assertSame([3, 2, 1, 1], [$row->jumlah_mahasiswa, $row->jumlah_dpl, $row->jumlah_pic, $row->jumlah_ketua_kelompok]);
    }

    public function test_data_sorted_by_name_with_nim_and_nama_only(): void
    {
        $data = $this->detail('mahasiswa')->assertOk()->json('data');

        $this->assertSame(['Andi', 'Budi', 'Citra'], array_column($data, 'nama'));
        $this->assertSame(['nim' => Mahasiswa::where('nama', 'Andi')->value('nim'), 'nama' => 'Andi'], $data[0]);

        $dpl = $this->detail('dpl')->json('data.0');
        $this->assertSame(['nim', 'nama'], array_keys($dpl));
        $this->assertSame(Dpl::where('nama', $dpl['nama'])->value('nidn'), $dpl['nim']);
    }

    public function test_response_never_contains_email(): void
    {
        foreach (['mahasiswa', 'dpl', 'pic', 'ketua'] as $jenis) {
            $response = $this->detail($jenis)->assertOk();
            $this->assertStringNotContainsString('"email"', $response->getContent());
            $this->assertStringNotContainsString('@', $response->getContent());
        }
    }

    public function test_pic_returns_kode_pt_nama_pt_and_nama_user(): void
    {
        $this->detail('pic')->assertOk()
            ->assertExactJson([
                'nama_pt' => $this->pt->nm_lemb,
                'jenis' => 'pic',
                'data' => [['kode_pt' => (string) $this->pt->npsn, 'nama_pt' => $this->pt->nm_lemb, 'nama_user' => 'PIC Kampus']],
            ]);
    }

    public function test_ketua_listed_once_per_email(): void
    {
        $this->detail('ketua')->assertOk()->assertJsonPath('data.0.nama', 'Budi')->assertJsonCount(1, 'data')
            ->assertJsonMissingPath('data.0.email');
    }

    public function test_ketua_with_duplicate_email_rows_returns_nim_and_nama_from_same_row(): void
    {
        Mahasiswa::factory()->create(['kodept' => $this->pt->npsn, 'nama' => 'Zed', 'nim' => '1', 'email' => 'dobel@pps.test']);
        Mahasiswa::factory()->create(['kodept' => $this->pt->npsn, 'nama' => 'Abe', 'nim' => '9', 'email' => 'dobel@pps.test']);
        Pjdesa::create(['email' => 'dobel@pps.test']);

        $total = app(DataPesertaSummary::class)->all()->firstWhere('kodept', $this->pt->npsn)->jumlah_ketua_kelompok;
        $data = $this->detail('ketua')->assertOk()->assertJsonCount($total, 'data')->json('data');

        $this->assertSame(2, $total);
        $names = array_column($data, 'nama');
        $sorted = $names;
        sort($sorted);
        $this->assertSame($sorted, $names);

        $dobel = collect($data)->firstWhere(fn ($row) => $row['nama'] !== 'Budi');
        $this->assertContains([$dobel['nim'], $dobel['nama']], [['1', 'Zed'], ['9', 'Abe']]);
    }
}
