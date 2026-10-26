<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

use App\Models\Faq;
use App\Models\Satuanpendidikan;

class FaqTest extends TestCase
{
    use RefreshDatabase;

    private const AJAX = ['X-Requested-With' => 'XMLHttpRequest'];

    private const XSS = '<script>alert(1)</script>';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'pertanyaan' => 'Kapan pendaftaran KKN dibuka?',
            'jawaban' => "Pendaftaran dibuka awal bulan.\nCek pengumuman resmi.",
        ];
    }

    private function loginNonAdmin(string $role): void
    {
        $attributes = $role === 'pt' ? ['email' => Satuanpendidikan::factory()->create()->npsn] : [];
        $this->loginAs($role, $attributes);
    }

    public function test_admin_can_open_index_listdata_tambah_and_edit(): void
    {
        $this->loginAs('admin');
        $faq = Faq::factory()->create(['pertanyaan' => 'Apa itu KKN?', 'jawaban' => 'Kuliah kerja nyata.']);

        $this->get('faq')->assertOk()->assertSee(json_encode(url('faq/listdata')), false)->assertSee(url('faq/tambah'), false);
        $this->get('faq/listdata')->assertOk()
            ->assertSeeInOrder(['>No</th>', '>Pertanyaan</th>', '>Jawaban</th>', '>Aksi</th>'], false)
            ->assertSee(route('faq.listdataserver'), false);
        $this->get('faq/tambah')->assertOk()->assertSee('name="pertanyaan"', false)->assertSee('name="jawaban"', false);
        $this->get('faq/edit/'.$faq->id)->assertOk()
            ->assertSee('Apa itu KKN?')
            ->assertSee('Kuliah kerja nyata.')
            ->assertSee('value="'.$faq->id.'"', false);
        $this->get('faq/edit/999999')->assertNotFound();
    }

    public function test_listdataserver_numbers_rows_in_creation_order(): void
    {
        $this->loginAs('admin');
        Faq::factory()->create(['pertanyaan' => 'Pertama?']);
        Faq::factory()->create(['pertanyaan' => 'Kedua?']);

        $this->getJson('faq/listdataserver?draw=1&start=0&length=10', self::AJAX)
            ->assertOk()
            ->assertJsonPath('recordsTotal', 2)
            ->assertJsonPath('data.0.DT_RowIndex', 1)
            ->assertJsonPath('data.0.pertanyaan', '<div class="text-wrap">Pertama?</div>')
            ->assertJsonPath('data.1.DT_RowIndex', 2)
            ->assertJsonPath('data.1.pertanyaan', '<div class="text-wrap">Kedua?</div>')
            ->assertJsonMissingPath('data.0.id');
    }

    public function test_listdataserver_empty_returns_no_rows_and_requires_ajax(): void
    {
        $this->loginAs('admin');

        $this->getJson('faq/listdataserver?draw=1&start=0&length=10', self::AJAX)
            ->assertOk()->assertJsonPath('recordsTotal', 0)->assertJsonCount(0, 'data');
        $this->get('faq/listdataserver')->assertNotFound();
    }

    public function test_listdataserver_escapes_html_and_keeps_newlines(): void
    {
        $this->loginAs('admin');
        Faq::factory()->create(['pertanyaan' => self::XSS, 'jawaban' => self::XSS."\n<img src=x onerror=alert(1)>"]);

        $response = $this->getJson('faq/listdataserver?draw=1&start=0&length=10', self::AJAX)
            ->assertOk()
            ->assertJsonPath('data.0.pertanyaan', '<div class="text-wrap">&lt;script&gt;alert(1)&lt;/script&gt;</div>')
            ->assertJsonPath('data.0.jawaban', "<div class=\"text-wrap\">&lt;script&gt;alert(1)&lt;/script&gt;<br />\n&lt;img src=x onerror=alert(1)&gt;</div>");

        $this->assertStringNotContainsString('<script>', implode('', array_column($response->json('data'), 'pertanyaan')));
    }

    public function test_admin_can_insert_faq(): void
    {
        $this->loginAs('admin');

        $this->put('faq/insert', $this->payload())->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('faqs', $this->payload());
        $this->assertDatabaseCount('faqs', 1);
    }

    public function test_insert_accepts_max_length_boundaries(): void
    {
        $this->loginAs('admin');

        $this->put('faq/insert', ['pertanyaan' => str_repeat('a', 255), 'jawaban' => str_repeat('b', 10000)])
            ->assertJson(['success' => true]);

        $this->assertDatabaseCount('faqs', 1);
    }

    public function test_admin_can_update_faq(): void
    {
        $this->loginAs('admin');
        $faq = Faq::factory()->create();

        $this->put('faq/update', $this->payload(['id' => $faq->id, 'pertanyaan' => 'Pertanyaan baru?']))
            ->assertOk()->assertJson(['success' => true]);

        $faq->refresh();
        $this->assertSame('Pertanyaan baru?', $faq->pertanyaan);
        $this->assertSame($this->payload()['jawaban'], $faq->jawaban);
    }

    public function test_admin_can_destroy_faq(): void
    {
        $this->loginAs('admin');
        $faq = Faq::factory()->create();
        $other = Faq::factory()->create();

        $this->put('faq/destroy', ['id' => $faq->id])->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
        $this->assertDatabaseHas('faqs', ['id' => $other->id]);
    }

    public function test_destroy_with_missing_or_invalid_id_returns_not_found(): void
    {
        $this->loginAs('admin');
        Faq::factory()->create();

        $this->put('faq/destroy', ['id' => 999999])->assertNotFound()->assertJson(['success' => false]);
        $this->put('faq/destroy', [])->assertNotFound()->assertJson(['success' => false]);
        $this->put('faq/destroy', ['id' => ['1']])->assertNotFound()->assertJson(['success' => false]);

        $this->assertDatabaseCount('faqs', 1);
    }

    public static function invalidPayloads(): array
    {
        return [
            'pertanyaan kosong' => [['pertanyaan' => ''], ['pertanyaan']],
            'jawaban kosong' => [['jawaban' => ''], ['jawaban']],
            'keduanya kosong' => [['pertanyaan' => '', 'jawaban' => ''], ['pertanyaan', 'jawaban']],
            'pertanyaan > 255' => [['pertanyaan' => str_repeat('a', 256)], ['pertanyaan']],
            'jawaban > 10000' => [['jawaban' => str_repeat('b', 10001)], ['jawaban']],
            'pertanyaan array' => [['pertanyaan' => ['x']], ['pertanyaan']],
        ];
    }

    #[DataProvider('invalidPayloads')]
    public function test_insert_rejects_invalid_payload(array $overrides, array $errorFields): void
    {
        $this->loginAs('admin');

        $this->put('faq/insert', $this->payload($overrides))
            ->assertOk()
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors($errorFields, 'errors');

        $this->assertDatabaseCount('faqs', 0);
    }

    #[DataProvider('invalidPayloads')]
    public function test_update_rejects_invalid_payload(array $overrides, array $errorFields): void
    {
        $this->loginAs('admin');
        $faq = Faq::factory()->create(['pertanyaan' => 'Asli?']);

        $this->put('faq/update', $this->payload($overrides + ['id' => $faq->id]))
            ->assertOk()
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors($errorFields, 'errors');

        $this->assertSame('Asli?', $faq->fresh()->pertanyaan);
    }

    public static function invalidUpdateIds(): array
    {
        return [
            'tanpa id' => [null],
            'id tidak ada' => [999999],
            'id bukan angka' => ['abc'],
        ];
    }

    #[DataProvider('invalidUpdateIds')]
    public function test_update_rejects_missing_or_unknown_id(mixed $faqId): void
    {
        $this->loginAs('admin');
        $faq = Faq::factory()->create(['pertanyaan' => 'Asli?']);
        $payload = $this->payload(['pertanyaan' => 'Diubah?']);
        if ($faqId !== null) {
            $payload['id'] = $faqId;
        }

        $this->put('faq/update', $payload)
            ->assertOk()
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors(['id'], 'errors');

        $this->assertSame('Asli?', $faq->fresh()->pertanyaan);
    }

    public static function nonAdminRoles(): array
    {
        return ['mahasiswa' => ['mahasiswa'], 'pt' => ['pt'], 'dpl' => ['dpl'], 'pemda' => ['pemda'], 'kepala' => ['kepala']];
    }

    #[DataProvider('nonAdminRoles')]
    public function test_non_admin_is_forbidden_on_all_faq_routes(string $role): void
    {
        $faq = Faq::factory()->create(['pertanyaan' => 'Asli?']);
        $this->loginNonAdmin($role);

        foreach (['faq', 'faq/listdata', 'faq/tambah', 'faq/edit/'.$faq->id] as $uri) {
            $this->get($uri)->assertForbidden();
        }
        $this->getJson('faq/listdataserver', self::AJAX)->assertForbidden();

        $this->put('faq/insert', $this->payload())->assertForbidden();
        $this->put('faq/update', $this->payload(['id' => $faq->id, 'pertanyaan' => 'Diretas?']))->assertForbidden();
        $this->put('faq/destroy', ['id' => $faq->id])->assertForbidden();

        $this->assertDatabaseCount('faqs', 1);
        $this->assertSame('Asli?', $faq->fresh()->pertanyaan);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $faq = Faq::factory()->create(['pertanyaan' => 'Asli?']);

        foreach (['faq', 'faq/listdata', 'faq/tambah', 'faq/edit/'.$faq->id] as $uri) {
            $this->get($uri)->assertRedirect(route('login'));
        }
        $this->put('faq/insert', $this->payload())->assertRedirect(route('login'));
        $this->put('faq/update', $this->payload(['id' => $faq->id]))->assertRedirect(route('login'));
        $this->put('faq/destroy', ['id' => $faq->id])->assertRedirect(route('login'));

        $this->assertDatabaseCount('faqs', 1);
        $this->assertSame('Asli?', $faq->fresh()->pertanyaan);
    }

    public function test_faq_menu_only_visible_to_admin(): void
    {
        $menuLink = 'href="'.url('faq').'"';

        $this->loginAs('admin');
        $this->get('home')->assertOk()->assertSee($menuLink, false);

        foreach (['mahasiswa', 'pt'] as $role) {
            $this->loginNonAdmin($role);
            $this->get('home')->assertOk()->assertDontSee($menuLink, false);
        }
    }

    public function test_landing_cache_is_reset_after_insert_update_and_delete(): void
    {
        $this->loginAs('admin');

        $this->get('/')->assertOk()->assertDontSee('id="faq"', false);
        $this->assertTrue(Cache::has(Faq::PUBLIC_CACHE_KEY));

        $this->put('faq/insert', $this->payload(['pertanyaan' => 'Versi satu?']))->assertJson(['success' => true]);
        $this->assertFalse(Cache::has(Faq::PUBLIC_CACHE_KEY));
        $this->get('/')->assertOk()->assertSee('id="faq"', false)->assertSee('Versi satu?');

        $faq = Faq::firstOrFail();
        $this->put('faq/update', $this->payload(['id' => $faq->id, 'pertanyaan' => 'Versi dua?']))->assertJson(['success' => true]);
        $this->assertFalse(Cache::has(Faq::PUBLIC_CACHE_KEY));
        $this->get('/')->assertOk()->assertSee('Versi dua?')->assertDontSee('Versi satu?');

        $this->put('faq/destroy', ['id' => $faq->id])->assertJson(['success' => true]);
        $this->assertFalse(Cache::has(Faq::PUBLIC_CACHE_KEY));
        $this->get('/')->assertOk()->assertDontSee('Versi dua?')->assertDontSee('id="faq"', false);
    }

    public function test_write_routes_are_throttled_per_user(): void
    {
        $this->loginAs('admin');

        for ($i = 0; $i < 10; $i++) {
            $this->put('faq/insert', $this->payload())->assertOk();
        }
        $this->put('faq/insert', $this->payload())->assertStatus(429);

        $this->assertDatabaseCount('faqs', 10);
    }

    // Form dikirim POST + _method=PUT (multipart)
    private function submit(string $uri, array $data)
    {
        return $this->post($uri, $data + ['_method' => 'PUT']);
    }

    private function image(string $name = 'jawaban.jpg', int $kilobytes = 100): UploadedFile
    {
        return UploadedFile::fake()->image($name, 640, 480)->size($kilobytes);
    }

    // FAQ dengan file gambar nyata di disk fake
    private function faqWithStoredImage(string $state = 'image'): Faq
    {
        $faq = Faq::factory()->{$state}()->create();
        Storage::disk('public')->put($faq->answer_image, 'old-image');

        return $faq;
    }

    public function test_insert_image_type_stores_file_and_nulls_jawaban(): void
    {
        Storage::fake('public');
        $this->loginAs('admin');

        $this->submit('faq/insert', $this->payload(['answer_type' => 'image', 'answer_image' => $this->image()]))
            ->assertOk()->assertJson(['success' => true]);

        $faq = Faq::sole();
        $this->assertSame('image', $faq->answer_type);
        $this->assertNull($faq->jawaban);
        $this->assertStringStartsWith('faq/', $faq->answer_image);
        $this->assertStringNotContainsString('jawaban.jpg', $faq->answer_image);
        Storage::disk('public')->assertExists($faq->answer_image);
        $this->assertTrue($faq->hasImage());
        $this->assertFalse($faq->hasText());
    }

    public function test_insert_both_type_stores_text_and_file(): void
    {
        Storage::fake('public');
        $this->loginAs('admin');

        $this->submit('faq/insert', $this->payload(['answer_type' => 'both', 'answer_image' => $this->image('a.png')]))
            ->assertJson(['success' => true]);

        $faq = Faq::sole();
        $this->assertSame('both', $faq->answer_type);
        $this->assertSame($this->payload()['jawaban'], $faq->jawaban);
        Storage::disk('public')->assertExists($faq->answer_image);
        $this->assertTrue($faq->hasText() && $faq->hasImage());
    }

    public function test_insert_text_type_ignores_uploaded_file(): void
    {
        Storage::fake('public');
        $this->loginAs('admin');

        $this->submit('faq/insert', $this->payload(['answer_type' => 'text', 'answer_image' => $this->image()]))
            ->assertJson(['success' => true]);

        $faq = Faq::sole();
        $this->assertSame('text', $faq->answer_type);
        $this->assertNull($faq->answer_image);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_insert_without_answer_type_defaults_to_text(): void
    {
        $this->loginAs('admin');

        $this->put('faq/insert', $this->payload())->assertJson(['success' => true]);

        $this->assertSame('text', Faq::sole()->answer_type);
    }

    public static function invalidUploadPayloads(): array
    {
        return [
            'image tanpa file' => [fn () => ['answer_type' => 'image'], ['answer_image']],
            'both tanpa file' => [fn () => ['answer_type' => 'both'], ['answer_image']],
            'both tanpa jawaban' => [fn () => ['answer_type' => 'both', 'jawaban' => '', 'answer_image' => UploadedFile::fake()->image('a.jpg')], ['jawaban']],
            'text tanpa jawaban' => [fn () => ['answer_type' => 'text', 'jawaban' => ''], ['jawaban']],
            'mime svg' => [fn () => ['answer_type' => 'image', 'answer_image' => UploadedFile::fake()->create('a.svg', 10, 'image/svg+xml')], ['answer_image']],
            'mime gif' => [fn () => ['answer_type' => 'image', 'answer_image' => UploadedFile::fake()->image('a.gif')], ['answer_image']],
            'bukan gambar' => [fn () => ['answer_type' => 'image', 'answer_image' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')], ['answer_image']],
            'lebih dari 2MB' => [fn () => ['answer_type' => 'image', 'answer_image' => UploadedFile::fake()->image('a.jpg')->size(2049)], ['answer_image']],
            'tipe tidak valid' => [fn () => ['answer_type' => 'video'], ['answer_type']],
            'tipe array' => [fn () => ['answer_type' => ['image']], ['answer_type']],
        ];
    }

    #[DataProvider('invalidUploadPayloads')]
    public function test_insert_rejects_invalid_answer_type_or_image(\Closure $overrides, array $errorFields): void
    {
        Storage::fake('public');
        $this->loginAs('admin');

        $this->submit('faq/insert', $this->payload($overrides()))
            ->assertOk()
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors($errorFields, 'errors');

        $this->assertDatabaseCount('faqs', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_insert_accepts_image_at_2mb_boundary_and_webp(): void
    {
        Storage::fake('public');
        $this->loginAs('admin');

        $this->submit('faq/insert', $this->payload(['answer_type' => 'image', 'answer_image' => $this->image('a.jpg', 2048)]))
            ->assertJson(['success' => true]);
        $this->submit('faq/insert', $this->payload(['answer_type' => 'image', 'answer_image' => $this->image('b.webp')]))
            ->assertJson(['success' => true]);

        $this->assertDatabaseCount('faqs', 2);
        $this->assertCount(2, Storage::disk('public')->allFiles('faq'));
    }

    public function test_update_with_new_image_deletes_old_file(): void
    {
        Storage::fake('public');
        $this->loginAs('admin');
        $faq = $this->faqWithStoredImage();
        $old = $faq->answer_image;

        $this->submit('faq/update', $this->payload(['id' => $faq->id, 'answer_type' => 'image', 'answer_image' => $this->image()]))
            ->assertJson(['success' => true]);

        $faq->refresh();
        $this->assertNotSame($old, $faq->answer_image);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($faq->answer_image);
        $this->assertNull($faq->jawaban);
    }

    public function test_update_to_text_deletes_image(): void
    {
        Storage::fake('public');
        $this->loginAs('admin');
        $faq = $this->faqWithStoredImage('both');
        $old = $faq->answer_image;

        $this->submit('faq/update', $this->payload(['id' => $faq->id, 'answer_type' => 'text']))
            ->assertJson(['success' => true]);

        $faq->refresh();
        $this->assertSame('text', $faq->answer_type);
        $this->assertNull($faq->answer_image);
        $this->assertNull($faq->answer_image_url);
        Storage::disk('public')->assertMissing($old);
    }

    public function test_update_image_type_without_new_file_keeps_existing_image(): void
    {
        Storage::fake('public');
        $this->loginAs('admin');
        $faq = $this->faqWithStoredImage();
        $old = $faq->answer_image;

        $this->submit('faq/update', $this->payload(['id' => $faq->id, 'answer_type' => 'image', 'pertanyaan' => 'Diubah?']))
            ->assertJson(['success' => true]);
        // answer_type tidak dikirim: mengikuti tipe record saat ini
        $this->submit('faq/update', ['id' => $faq->id, 'pertanyaan' => 'Diubah lagi?'])
            ->assertJson(['success' => true]);

        $faq->refresh();
        $this->assertSame('Diubah lagi?', $faq->pertanyaan);
        $this->assertSame('image', $faq->answer_type);
        $this->assertSame($old, $faq->answer_image);
        Storage::disk('public')->assertExists($old);
    }

    public function test_update_from_text_to_image_requires_file(): void
    {
        Storage::fake('public');
        $this->loginAs('admin');
        $faq = Faq::factory()->create();

        $this->submit('faq/update', $this->payload(['id' => $faq->id, 'answer_type' => 'image']))
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors(['answer_image'], 'errors');

        $this->assertSame('text', $faq->fresh()->answer_type);
    }

    public function test_destroy_deletes_image_file(): void
    {
        Storage::fake('public');
        $this->loginAs('admin');
        $faq = $this->faqWithStoredImage('both');

        $this->put('faq/destroy', ['id' => $faq->id])->assertJson(['success' => true]);

        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
        Storage::disk('public')->assertMissing($faq->answer_image);
    }

    public function test_landing_renders_answer_by_type(): void
    {
        Storage::fake('public');
        $text = Faq::factory()->create(['pertanyaan' => 'Teks?', 'jawaban' => 'Jawaban teks saja']);
        $image = Faq::factory()->image()->create(['pertanyaan' => 'Gambar?']);
        $both = Faq::factory()->both()->create(['pertanyaan' => 'Keduanya?', 'jawaban' => 'Jawaban keduanya']);
        // Data lama tipe text yang masih menyimpan path gambar tidak boleh menampilkan gambar
        Faq::factory()->create(['pertanyaan' => 'Basi?', 'jawaban' => 'Teks basi', 'answer_image' => 'faq/stale.jpg']);

        $response = $this->get('/')->assertOk();
        $html = $response->getContent();

        $this->assertSame(3, substr_count($html, 'class="faq-answer-text"'));
        $this->assertSame(2, substr_count($html, 'class="faq-answer-img"'));
        $response->assertSee('src="'.Storage::disk('public')->url($image->answer_image).'"', false)
            ->assertSee('src="'.Storage::disk('public')->url($both->answer_image).'"', false)
            ->assertDontSee('faq/stale.jpg', false)
            ->assertSeeInOrder(['id="faq-'.$both->id.'"', 'class="faq-answer-text">Jawaban keduanya</div>', 'class="faq-answer-img"'], false);

        $imageBlock = $this->between($html, 'id="faq-'.$image->id.'"', 'id="faq-'.$both->id.'-head"');
        $this->assertStringNotContainsString('faq-answer-text', $imageBlock);
        $this->assertStringContainsString('faq-answer-img', $imageBlock);

        $textBlock = $this->between($html, 'id="faq-'.$text->id.'"', 'id="faq-'.$image->id.'-head"');
        $this->assertStringNotContainsString('faq-answer-img', $textBlock);
    }

    public function test_landing_escapes_question_in_image_alt(): void
    {
        Storage::fake('public');
        Faq::factory()->image()->create(['pertanyaan' => '"><script>alert(1)</script>']);

        $this->get('/')->assertOk()
            ->assertSee('alt="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    #[DataProvider('nonAdminRoles')]
    public function test_non_admin_upload_is_forbidden_and_stores_nothing(string $role): void
    {
        Storage::fake('public');
        $faq = $this->faqWithStoredImage();
        $old = $faq->answer_image;
        $this->loginNonAdmin($role);

        $this->submit('faq/insert', $this->payload(['answer_type' => 'image', 'answer_image' => $this->image()]))->assertForbidden();
        $this->submit('faq/update', $this->payload(['id' => $faq->id, 'answer_type' => 'text']))->assertForbidden();
        $this->submit('faq/update', $this->payload(['id' => $faq->id, 'answer_type' => 'image', 'answer_image' => $this->image()]))->assertForbidden();

        $this->assertDatabaseCount('faqs', 1);
        $this->assertSame($old, $faq->fresh()->answer_image);
        $this->assertSame([$old], Storage::disk('public')->allFiles());
    }

    public function test_guest_upload_is_redirected_and_stores_nothing(): void
    {
        Storage::fake('public');

        $this->submit('faq/insert', $this->payload(['answer_type' => 'image', 'answer_image' => $this->image()]))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('faqs', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    private function between(string $haystack, string $start, string $end): string
    {
        $from = strpos($haystack, $start);
        $this->assertNotFalse($from);
        $to = strpos($haystack, $end, $from);

        return substr($haystack, $from, $to === false ? null : $to - $from);
    }
}
