<?php

namespace Tests\Feature;

use App\Models\KategoriKegiatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

// URL lama sebelum rename KPI (ADR 2026-10-10) wajib redirect 301 ke URL baru
class LegacyKpiRedirectTest extends TestCase
{
    use RefreshDatabase;

    public static function legacyUrls(): array
    {
        return [
            'kpi root' => ['kpi', 'kategori-kegiatan'],
            'kpi sub-path' => ['kpi/listdata', 'kategori-kegiatan/listdata'],
            'kpi export' => ['kpi/export', 'kategori-kegiatan/export'],
            'kpi edit uuid' => ['kpi/edit/0199a1b2-0000-7000-8000-000000000001', 'kategori-kegiatan/edit/0199a1b2-0000-7000-8000-000000000001'],
            'kpicapaian root' => ['kpicapaian', 'capaiankegiatan'],
            'kpicapaian tambah' => ['kpicapaian/tambah', 'capaiankegiatan/tambah'],
            'lapcapaiankpi root' => ['lapcapaiankpi', 'lapcapaiankegiatan'],
            'lapcapaiankpi export' => ['lapcapaiankpi/export', 'lapcapaiankegiatan/export'],
            'dashboardkpi root' => ['dashboardkpi', 'dashboard-pengurangan-sampah'],
            'dashboardkpi export' => ['dashboardkpi/export-capaian', 'dashboard-pengurangan-sampah/export-capaian'],
        ];
    }

    #[DataProvider('legacyUrls')]
    public function test_url_lama_redirect_301_ke_url_baru(string $old, string $new): void
    {
        $this->loginAs('admin');

        $this->get($old)->assertStatus(301)->assertHeader('Location', url($new));
    }

    public function test_redirect_berlaku_untuk_guest(): void
    {
        $this->get('kpi')->assertStatus(301)->assertHeader('Location', url('kategori-kegiatan'));
        $this->get('dashboardkpi/export-capaian')->assertStatus(301)
            ->assertHeader('Location', url('dashboard-pengurangan-sampah/export-capaian'));
    }

    public function test_query_string_ikut_diteruskan(): void
    {
        $this->loginAs('admin');

        $location = $this->get('dashboardkpi?kodept=123&kecamatan=abc&bulan=2026-09')
            ->assertStatus(301)->headers->get('Location');
        $this->assertStringStartsWith(url('dashboard-pengurangan-sampah').'?', $location);
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame(['bulan' => '2026-09', 'kecamatan' => 'abc', 'kodept' => '123'], $query);

        $location = $this->get('lapcapaiankpi/listdataserver?draw=1&search[value]=Kompos&order[0][dir]=desc')
            ->assertStatus(301)->headers->get('Location');
        $this->assertStringStartsWith(url('lapcapaiankegiatan/listdataserver').'?', $location);
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame('1', $query['draw']);
        $this->assertSame('Kompos', $query['search']['value']);
        $this->assertSame('desc', $query['order'][0]['dir']);
    }

    public function test_redirect_lalu_halaman_baru_bisa_dibuka(): void
    {
        $this->loginAs('admin');

        $this->followingRedirects()->get('kpi')->assertOk();
    }

    public function test_redirect_tidak_keluar_dari_domain_aplikasi(): void
    {
        foreach (['kpi//evil.test', 'kpicapaian/%2F%2Fevil.test'] as $uri) {
            $location = (string) $this->get($uri)->headers->get('Location');
            $this->assertSame(parse_url(url('/'), PHP_URL_HOST), parse_url($location, PHP_URL_HOST), $uri);
        }
    }

    public static function pathBerbahaya(): array
    {
        return [
            'CR' => ['kpi/a%0Db'],
            'LF' => ['kpi/a%0Ab'],
            'CRLF header injection' => ['kpicapaian/x%0D%0ASet-Cookie:%20a=b'],
            'NUL' => ['lapcapaiankpi/a%00b'],
            'dot-dot' => ['dashboardkpi/..//evil.test'],
            'dot-dot tengah' => ['kpi/a/../b'],
            'dot-dot encoded' => ['kpi/%2E%2E/b'],
            'dot-dot double encoded' => ['kpi/%252e%252e/b'],
            'slash encoded double' => ['kpi/a%252F..'],
        ];
    }

    #[DataProvider('pathBerbahaya')]
    public function test_path_berbahaya_ditolak_404(string $uri): void
    {
        $this->get($uri)->assertNotFound()->assertHeaderMissing('Location');
    }

    public function test_path_mirip_dot_tetap_redirect(): void
    {
        $this->get('kpi/a..b/.x')->assertStatus(301)->assertHeader('Location', url('kategori-kegiatan/a..b/.x'));
    }

    public function test_hak_akses_tetap_dicek_di_url_baru(): void
    {
        // Redirect tidak membuka akses: mahasiswa diteruskan lalu ditolak middleware role
        $this->loginAs('mahasiswa');

        $this->get('kpi')->assertStatus(301);
        $this->get('kategori-kegiatan')->assertRedirect(route('home'));
        $this->get('kategori-kegiatan/export')->assertRedirect(route('home'));
    }

    public function test_method_non_get_ke_url_lama_tidak_diproses(): void
    {
        $this->loginAs('admin');

        $this->put('kpi/insert', ['nama_kategori' => 'Lewat URL Lama'])->assertStatus(405);
        $this->assertSame(0, KategoriKegiatan::where('nama_kategori', 'Lewat URL Lama')->count());
    }

    public function test_url_mirip_tapi_bukan_lama_tidak_ikut_redirect(): void
    {
        $this->loginAs('admin');

        $this->get('kpisampah')->assertNotFound();
        $this->get('kpitarget')->assertNotFound();
    }

    public static function menuPerRole(): array
    {
        return [
            'admin' => ['admin', ['kategori-kegiatan', 'lapcapaiankegiatan'], ['capaiankegiatan"']],
            'dpl' => ['dpl', ['lapcapaiankegiatan'], ['kategori-kegiatan', 'capaiankegiatan"']],
            'pt' => ['pt', ['lapcapaiankegiatan'], ['kategori-kegiatan', 'capaiankegiatan"']],
            'kepala' => ['kepala', ['lapcapaiankegiatan'], ['kategori-kegiatan', 'capaiankegiatan"']],
            'pemda' => ['pemda', ['lapcapaiankegiatan'], ['kategori-kegiatan', 'capaiankegiatan"']],
            'mahasiswa' => ['mahasiswa', ['capaiankegiatan"'], ['kategori-kegiatan', 'lapcapaiankegiatan']],
        ];
    }

    #[DataProvider('menuPerRole')]
    public function test_menu_role_memakai_url_baru(string $role, array $tampil, array $tersembunyi): void
    {
        $this->loginAs($role);

        // DPL tanpa data mentoring diarahkan dari home; pakai halaman lain yang memuat layout
        $html = $this->get($role === 'dpl' ? 'lapcapaiankegiatan' : 'home')->assertOk()->getContent();
        foreach ($tampil as $path) {
            $this->assertStringContainsString('href="'.url($path), $html, "$role: $path");
        }
        foreach ($tersembunyi as $path) {
            $this->assertStringNotContainsString('href="'.url($path), $html, "$role: $path");
        }
        foreach (['kpi', 'kpicapaian', 'lapcapaiankpi', 'dashboardkpi'] as $lama) {
            $this->assertStringNotContainsString('href="'.url($lama).'"', $html, "$role: link lama $lama");
        }
        $this->assertDoesNotMatchRegularExpression('/>\s*[^<]*\bKPI\b[^<]*</', $html, "$role: label KPI");
    }

    public function test_route_baru_terdaftar_dengan_nama_baru(): void
    {
        foreach ([
            'kategori-kegiatan.listdata', 'kategori-kegiatan.listdataserver', 'kategori-kegiatan.export',
            'capaiankegiatan.listdata', 'capaiankegiatan.listdataserver',
            'lapcapaiankegiatan.listdata', 'lapcapaiankegiatan.listdataserver',
            'dashboard-pengurangan-sampah', 'dashboard-pengurangan-sampah.export-capaian',
        ] as $name) {
            $this->assertTrue(Route::has($name), $name);
        }
        foreach (['kpi.listdata', 'kpicapaian.listdata', 'lapcapaiankpi.listdata', 'dashboardkpi', 'dashboardkpi.export-capaian'] as $name) {
            $this->assertFalse(Route::has($name), $name);
        }
    }
}
