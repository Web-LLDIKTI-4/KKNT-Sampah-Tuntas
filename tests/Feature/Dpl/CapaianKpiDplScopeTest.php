<?php

namespace Tests\Feature\Dpl;

use App\Exports\CapaianKegiatanExport;
use App\Models\CapaianKegiatan;
use App\Models\Desa;
use App\Models\Dplmentoring;
use App\Models\Mahasiswa;
use App\Models\Mahasiswa_lokasi;
use App\Models\Pjdesa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class CapaianKpiDplScopeTest extends TestCase
{
    use RefreshDatabase;

    private array $ajax = ['X-Requested-With' => 'XMLHttpRequest'];

    // Ketua kelompok (Pjdesa) dengan satu capaian; opsional dibimbing DPL
    private function ketua(string $nama, ?User $dpl = null, ?string $idDesa = null): Mahasiswa
    {
        $mhs = Mahasiswa::factory()->create(['nama' => $nama]);
        Pjdesa::create(['email' => $mhs->email, 'id_desa' => $idDesa ?? Desa::factory()->create()->id_desa]);
        CapaianKegiatan::factory()->create(['email' => $mhs->email]);
        if ($dpl) {
            Dplmentoring::create(['email_mahasiswa' => $mhs->email, 'email_dpl' => $dpl->email]);
        }

        return $mhs;
    }

    private function listdata(string $query = ''): TestResponse
    {
        $columns = '&columns[0][data]=pjdesa&columns[0][name]=pjdesa&columns[0][searchable]=true&columns[0][orderable]=true';

        return $this->getJson('lapcapaiankegiatan/listdataserver?draw=1&start=0&length=50'.$columns.$query, $this->ajax)
            ->assertOk();
    }

    public function test_dpl_listdata_only_shows_capaian_of_own_mentees(): void
    {
        $dplA = $this->loginAs('dpl');
        $dplB = User::factory()->role('dpl')->create();
        $mhsA = $this->ketua('Ketua A', $dplA);
        $this->ketua('Ketua B', $dplB);
        $this->ketua('Ketua Tanpa DPL');

        $data = $this->listdata()->assertJsonPath('recordsTotal', 1)->json('data');
        $this->assertSame([$mhsA->email], array_column($data, 'email'));

        // Global search tidak bisa keluar dari scope DPL
        $this->listdata('&search[value]=Ketua B')->assertJsonPath('recordsFiltered', 0);

        $this->actingAs($dplB);
        $this->listdata()->assertJsonPath('recordsTotal', 1)->assertJsonPath('data.0.pjdesa', 'Ketua B');
    }

    public function test_dpl_export_only_contains_capaian_of_own_mentees(): void
    {
        $dplA = $this->loginAs('dpl');
        $dplB = User::factory()->role('dpl')->create();
        $mhsA = $this->ketua('Ketua A', $dplA);
        $this->ketua('Ketua B', $dplB);
        $this->ketua('Ketua Tanpa DPL');

        $this->assertSame([$mhsA->email], (new CapaianKegiatanExport())->collection()->pluck('PJ Desa')->all());

        Excel::fake();
        $this->get('lapcapaiankegiatan/export')->assertOk();
    }

    public function test_dpl_without_mentees_sees_nothing(): void
    {
        $this->loginAs('dpl');
        $this->ketua('Ketua Lain', User::factory()->role('dpl')->create());

        $this->listdata()->assertJsonPath('recordsTotal', 0);
        $this->assertCount(0, (new CapaianKegiatanExport())->collection());
    }

    // Perilaku saat ini: DPL yang hanya membimbing anggota (bukan ketua) tidak melihat capaian kelompoknya
    public function test_dpl_mentoring_only_anggota_does_not_see_group_capaian_current_behavior(): void
    {
        $dpl = $this->loginAs('dpl');
        $idDesa = Desa::factory()->create()->id_desa;
        $this->ketua('Ketua Desa X', null, $idDesa);

        $anggota = Mahasiswa::factory()->create();
        Mahasiswa_lokasi::create([
            'tahun' => (int) date('Y'),
            'id_mahasiswa' => $anggota->id_mahasiswa,
            'id_desa' => $idDesa,
            'user_in_up' => $anggota->email,
        ]);
        Dplmentoring::create(['email_mahasiswa' => $anggota->email, 'email_dpl' => $dpl->email]);

        // Anggota sendiri melihat capaian ketua di desanya
        $this->assertSame(1, CapaianKegiatan::visibleToMahasiswa($anggota->email)->count());

        $this->listdata()->assertJsonPath('recordsTotal', 0);
        $this->assertCount(0, (new CapaianKegiatanExport())->collection());
    }

    public function test_mahasiswa_cannot_access_lapcapaian_listdata(): void
    {
        $this->get('lapcapaiankegiatan/listdataserver')->assertRedirect(route('login'));

        $this->loginAs('mahasiswa');
        $this->get('lapcapaiankegiatan/listdataserver', $this->ajax)->assertRedirect(route('home'));
    }
}
