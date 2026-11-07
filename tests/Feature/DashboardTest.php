<?php

namespace Tests\Feature;

use App\Models\Dpl;
use App\Models\Dplmentoring;
use App\Models\Logkegiatan;
use App\Models\Mahasiswa;
use App\Models\PendataanPemilahanSampah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_for_every_role(): void
    {
        foreach (['admin', 'mahasiswa', 'pt'] as $role) {
            $this->loginAs($role);
            $this->get('home')->assertOk();
        }
    }

    public function test_dpl_without_profile_is_sent_to_profile(): void
    {
        $this->loginAs('dpl');

        $this->get('home')->assertRedirect(url('profile'));
    }

    public function test_dpl_dashboard_counts_only_mentees(): void
    {
        $dpl = $this->loginAs('dpl');
        Dpl::factory()->create(['email' => $dpl->email]);
        $mhs = Mahasiswa::factory()->create();
        Dplmentoring::create(['email_mahasiswa' => $mhs->email, 'email_dpl' => $dpl->email]);
        Logkegiatan::factory()->count(2)->sequence(['tanggal' => '2026-01-01'], ['tanggal' => '2026-01-02'])->create(['email' => $mhs->email]);
        Logkegiatan::factory()->create(['email' => 'lain@pps.test']);

        $this->get('home')->assertOk()->assertViewHas('jumlahlogkegiatan', 2)->assertViewHas('jumlahmahasiswa', 1)->assertViewHas('jumlahdpl', 0);
    }

    public function test_pt_pages_render(): void
    {
        $this->loginAs('pt');

        $this->get('ptmahasiswa')->assertOk();
        $this->get('nilaifreeform')->assertNotFound();
    }

    public function test_population_waste_entries_are_not_counted_as_daily_activity_logs(): void
    {
        $user = $this->loginAs('mahasiswa');
        Logkegiatan::factory()->create(['email' => $user->email]);
        PendataanPemilahanSampah::factory()->create(['email' => $user->email]);

        $this->get('home')->assertOk()->assertViewHas('jumlahlogkegiatan', 1);
    }

    public function test_mahasiswa_home_counts_only_own_population_entries(): void
    {
        $user = $this->loginAs('mahasiswa');
        PendataanPemilahanSampah::factory()->count(2)->create(['email' => $user->email]);
        PendataanPemilahanSampah::factory()->count(3)->create(['email' => 'lain@pps.test']);

        $this->get('home')->assertOk()
            ->assertViewHas('jumlahpendataanpemilahan', 2)
            ->assertSee('2 <span class="fs-5">Data</span>', false);
    }

    public function test_mahasiswa_home_population_count_is_zero_without_data(): void
    {
        $this->loginAs('mahasiswa');
        PendataanPemilahanSampah::factory()->create(['email' => 'lain@pps.test']);

        $this->get('home')->assertOk()
            ->assertViewHas('jumlahpendataanpemilahan', 0)
            ->assertSee('0 <span class="fs-5">Data</span>', false);
    }

    public function test_population_card_renders_for_regular_and_pjdesa_mahasiswa(): void
    {
        foreach ([[], ['akses' => 'pjdesa']] as $attributes) {
            $user = $this->loginAs('mahasiswa', $attributes);
            Logkegiatan::factory()->create(['email' => $user->email]);
            PendataanPemilahanSampah::factory()->create(['email' => $user->email]);

            $this->get('home')->assertOk()
                ->assertViewHas('jumlahpendataanpemilahan', 1)
                ->assertViewMissing('jumlahlogharian')
                ->assertDontSee('Entri Log Harian')
                ->assertSee('<p class="mb-1">Log Harian</p>', false)
                ->assertSee('1 <span class="fs-5">Kegiatan</span>', false)
                ->assertSee('<a href="'.route('pendataanpemilahan.index').'" class="text-body">Pendataan Sampah Penduduk</a>', false);
        }
    }

    public function test_population_card_link_is_accessible(): void
    {
        $this->loginAs('mahasiswa');

        $this->get(route('pendataanpemilahan.index'))->assertOk();
    }

    public function test_guest_cannot_see_mahasiswa_home(): void
    {
        $this->get('home')->assertRedirect();
        $this->assertGuest();
    }
}
