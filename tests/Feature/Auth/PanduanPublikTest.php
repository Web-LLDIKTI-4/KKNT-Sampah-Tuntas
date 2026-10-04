<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

use App\Models\Panduan;
use Database\Seeders\PanduanSeeder;

class PanduanPublikTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(Panduan::DISK);
        Cache::forget(Panduan::PUBLIC_CACHE_KEY);
    }

    private function createPanduan(array $attributes = []): Panduan
    {
        $filePath = UploadedFile::fake()->create('panduan.pdf', 20, 'application/pdf')->store('panduan', Panduan::DISK);

        return Panduan::factory()->create($attributes + [
            'file_path' => $filePath,
            'nama_file' => 'Panduan Logbook.pdf',
            'is_aktif' => true,
        ]);
    }

    public function test_login_page_lists_only_active_panduan_without_internal_data(): void
    {
        $aktif = $this->createPanduan(['judul' => 'Panduan Aktif <script>x</script>']);
        $nonaktif = $this->createPanduan(['judul' => 'Panduan Draf Rahasia', 'is_aktif' => false]);

        $this->get('login')
            ->assertOk()
            ->assertSee('id="tabPanduan"', false)
            ->assertSee('id="panePanduan"', false)
            ->assertSee('Panduan Aktif &lt;script&gt;', false)
            ->assertDontSee('<script>x</script>', false)
            ->assertSee(route('panduan.unduh', $aktif->id_panduan), false)
            ->assertDontSee('Panduan Draf Rahasia')
            ->assertDontSee($nonaktif->id_panduan)
            ->assertDontSee($aktif->file_path)
            ->assertDontSee(route('panduan.download', $aktif->id_panduan), false);
    }

    public function test_login_page_shows_empty_state_when_no_panduan(): void
    {
        $this->get('login')->assertOk()->assertSee('id="tabPanduan"', false)->assertSee('Belum ada dokumen yang tersedia.');
    }

    public function test_guest_can_download_active_panduan(): void
    {
        $panduan = $this->createPanduan();

        $response = $this->get(route('panduan.unduh', $panduan->id_panduan))->assertOk()->assertDownload('Panduan Logbook.pdf');

        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('no-store', $response->headers->get('cache-control'));
        $this->assertSame('nosniff', $response->headers->get('x-content-type-options'));
    }

    public function test_logged_in_user_can_download_active_panduan(): void
    {
        $panduan = $this->createPanduan();
        $this->loginAs('mahasiswa');

        $this->get(route('panduan.unduh', $panduan->id_panduan))->assertOk();
    }

    public function test_inactive_unknown_invalid_or_missing_file_returns_404(): void
    {
        $nonaktif = $this->createPanduan(['is_aktif' => false]);
        $fileHilang = $this->createPanduan();
        Storage::disk(Panduan::DISK)->delete($fileHilang->file_path);

        $this->get(route('panduan.unduh', $nonaktif->id_panduan))->assertNotFound();
        $this->get(route('panduan.unduh', (string) Str::uuid()))->assertNotFound();
        $this->get('panduan/unduh/abc')->assertNotFound();
        $this->get(route('panduan.unduh', $fileHilang->id_panduan))->assertNotFound();
    }

    public function test_public_download_is_throttled_per_ip(): void
    {
        $panduan = $this->createPanduan();
        $url = route('panduan.unduh', $panduan->id_panduan);

        for ($attempt = 1; $attempt <= 30; $attempt++) {
            $this->get($url)->assertOk();
        }

        $this->get($url)->assertStatus(429);
    }

    public function test_deactivating_panduan_clears_login_cache(): void
    {
        $panduan = $this->createPanduan(['judul' => 'Panduan Sementara']);
        $this->get('login')->assertSee('Panduan Sementara');

        $this->loginAs('admin');
        $this->put('panduan/update', [
            'id_panduan' => $panduan->id_panduan,
            'judul' => 'Panduan Sementara',
            'is_aktif' => '0',
        ])->assertJson(['success' => true]);
        auth()->logout();

        $this->get('login')->assertDontSee('Panduan Sementara');
    }

    public function test_seeded_login_page_shows_only_active_rows(): void
    {
        Cache::put(Panduan::PUBLIC_CACHE_KEY, collect(), now()->addMinutes(10));
        $this->seed(PanduanSeeder::class);
        $this->assertNull(Cache::get(Panduan::PUBLIC_CACHE_KEY), 'Seeder wajib menghapus cache login');

        $html = $this->get('login')->assertOk()->getContent();

        $this->assertSame(Panduan::where('is_aktif', true)->count(), substr_count($html, 'class="panduan-item"'));
    }
}
