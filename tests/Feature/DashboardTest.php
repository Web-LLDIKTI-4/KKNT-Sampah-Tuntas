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
}
