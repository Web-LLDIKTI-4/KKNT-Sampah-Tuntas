<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Rules\Recaptcha;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RecaptchaLoginTest extends TestCase
{
    use RefreshDatabase;

    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.recaptcha.site_key' => 'test-site',
            'services.recaptcha.secret_key' => 'test-secret',
            'services.recaptcha.min_score' => 0.5,
            'services.recaptcha.hostname' => null,
        ]);
        Http::preventStrayRequests();

        User::factory()->role('admin')->create([
            'email' => 'admin@pps.test',
            'password' => Hash::make('Rahasia123'),
        ]);
    }

    private function fakeGoogle(array $body, int $status = 200): void
    {
        Http::fake([self::VERIFY_URL => Http::response($body, $status)]);
    }

    private function login(array $extra = ['g-recaptcha-response' => 'token-abc']): \Illuminate\Testing\TestResponse
    {
        return $this->put('login', ['username' => 'admin@pps.test', 'password' => 'Rahasia123'] + $extra);
    }

    private function assertRejected(\Illuminate\Testing\TestResponse $response): void
    {
        $response->assertOk()->assertJson(['success' => false, 'messages' => Recaptcha::MESSAGE]);
        $this->assertNotEmpty($response->json('token'));
        $this->assertGuest();
    }

    public function test_high_score_with_login_action_passes(): void
    {
        $this->fakeGoogle(['success' => true, 'score' => 0.9, 'action' => 'login', 'hostname' => 'localhost']);

        $this->login()->assertJson(['success' => true]);

        $this->assertAuthenticated();
        Http::assertSent(fn (Request $r) => $r->url() === self::VERIFY_URL
            && $r['secret'] === 'test-secret'
            && $r['response'] === 'token-abc');
    }

    public function test_score_equal_to_threshold_passes(): void
    {
        $this->fakeGoogle(['success' => true, 'score' => 0.5, 'action' => 'login']);

        $this->login()->assertJson(['success' => true]);
        $this->assertAuthenticated();
    }

    public function test_low_score_is_rejected(): void
    {
        $this->fakeGoogle(['success' => true, 'score' => 0.3, 'action' => 'login']);

        $this->assertRejected($this->login());
    }

    public function test_wrong_action_is_rejected(): void
    {
        $this->fakeGoogle(['success' => true, 'score' => 0.9, 'action' => 'register']);

        $this->assertRejected($this->login());
    }

    public function test_missing_score_is_rejected(): void
    {
        $this->fakeGoogle(['success' => true, 'action' => 'login']);

        $this->assertRejected($this->login());
    }

    public function test_missing_token_is_rejected_without_calling_google(): void
    {
        Http::fake();

        $this->assertRejected($this->login([]));
        Http::assertNothingSent();
    }

    public function test_array_token_is_rejected_without_calling_google(): void
    {
        Http::fake();

        $this->assertRejected($this->login(['g-recaptcha-response' => ['x']]));
        Http::assertNothingSent();
    }

    public function test_google_success_false_is_rejected(): void
    {
        $this->fakeGoogle(['success' => false, 'error-codes' => ['invalid-input-response']]);

        $this->assertRejected($this->login());
    }

    public function test_string_success_is_rejected(): void
    {
        $this->fakeGoogle(['success' => 'true', 'score' => 0.9, 'action' => 'login']);

        $this->assertRejected($this->login());
    }

    public function test_connection_error_is_fail_closed(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->assertRejected($this->login());
    }

    public function test_google_http_500_is_fail_closed(): void
    {
        $this->fakeGoogle(['success' => true, 'score' => 0.9, 'action' => 'login'], 500);

        $this->assertRejected($this->login());
    }

    public function test_empty_keys_disable_captcha(): void
    {
        config(['services.recaptcha.site_key' => '', 'services.recaptcha.secret_key' => '']);
        Http::fake();

        $this->login([])->assertJson(['success' => true]);

        $this->assertAuthenticated();
        Http::assertNothingSent();
    }

    public function test_only_site_key_set_keeps_captcha_disabled(): void
    {
        config(['services.recaptcha.secret_key' => '']);
        Http::fake();

        $this->login([])->assertJson(['success' => true]);
        Http::assertNothingSent();
    }

    public function test_captcha_failures_do_not_consume_rate_limiter(): void
    {
        $low = ['success' => true, 'score' => 0.1, 'action' => 'login'];
        Http::fake([self::VERIFY_URL => Http::sequence()
            ->push($low)->push($low)->push($low)->push($low)->push($low)->push($low)
            ->push(['success' => true, 'score' => 0.9, 'action' => 'login'])]);

        for ($i = 0; $i < 6; $i++) {
            $this->assertRejected($this->login());
        }

        $this->login()->assertJson(['success' => true]);
        $this->assertAuthenticated();
    }

    public function test_login_page_renders_script_and_hidden_input_when_enabled(): void
    {
        $this->get('login')
            ->assertOk()
            ->assertSee('recaptcha/api.js?render=test-site', false)
            ->assertSee('name="g-recaptcha-response"', false);
    }

    public function test_login_page_has_no_captcha_when_disabled(): void
    {
        config(['services.recaptcha.site_key' => '', 'services.recaptcha.secret_key' => '']);

        $this->get('login')
            ->assertOk()
            ->assertDontSee('recaptcha/api.js', false)
            ->assertDontSee('name="g-recaptcha-response"', false);
    }

    public function test_disabled_flag_skips_captcha_even_with_keys(): void
    {
        config(['services.recaptcha.enabled' => false]);
        Http::fake();

        $this->login([])->assertJson(['success' => true]);
        $this->assertAuthenticated();
        Http::assertNothingSent();
    }

    public function test_production_with_empty_keys_rejects_login(): void
    {
        config(['services.recaptcha.site_key' => '', 'services.recaptcha.secret_key' => '']);
        $this->app['env'] = 'production';
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Http::fake();

        $this->assertRejected($this->login());
        Http::assertNothingSent();
    }

    public function test_production_with_empty_keys_logs_error_when_no_token_sent(): void
    {
        // View tidak memuat script saat key kosong, jadi request nyata datang tanpa token
        config(['services.recaptcha.site_key' => '', 'services.recaptcha.secret_key' => '']);
        $this->app['env'] = 'production';
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Http::fake();
        Log::spy();

        $this->assertRejected($this->login([]));
        Log::shouldHaveReceived('error')->once();
    }

    public function test_production_with_empty_keys_hides_script(): void
    {
        config(['services.recaptcha.site_key' => '', 'services.recaptcha.secret_key' => '']);
        $this->app['env'] = 'production';

        $this->get('login')->assertOk()->assertDontSee('recaptcha/api.js', false);
    }

    public function test_disabled_flag_hides_script(): void
    {
        config(['services.recaptcha.enabled' => false]);

        $this->get('login')->assertOk()->assertDontSee('recaptcha/api.js', false);
    }

    public function test_token_longer_than_4096_is_rejected_without_calling_google(): void
    {
        Http::fake();

        $this->assertRejected($this->login(['g-recaptcha-response' => str_repeat('a', 4097)]));
        Http::assertNothingSent();
    }

    public function test_token_of_4096_chars_is_verified(): void
    {
        $this->fakeGoogle(['success' => true, 'score' => 0.9, 'action' => 'login']);

        $this->login(['g-recaptcha-response' => str_repeat('a', 4096)])->assertJson(['success' => true]);
    }

    public function test_wrong_hostname_is_rejected_when_configured(): void
    {
        config(['services.recaptcha.hostname' => 'sampah.example.id']);
        $this->fakeGoogle(['success' => true, 'score' => 0.9, 'action' => 'login', 'hostname' => 'evil.test']);

        $this->assertRejected($this->login());
    }

    public function test_matching_hostname_passes_when_configured(): void
    {
        config(['services.recaptcha.hostname' => 'sampah.example.id']);
        $this->fakeGoogle(['success' => true, 'score' => 0.9, 'action' => 'login', 'hostname' => 'sampah.example.id']);

        $this->login()->assertJson(['success' => true]);
    }

    public function test_empty_username_does_not_call_google(): void
    {
        Http::fake();

        $this->put('login', ['username' => '', 'password' => 'Rahasia123', 'g-recaptcha-response' => 'token-abc'])
            ->assertJson(['success' => false]);
        Http::assertNothingSent();
        $this->assertGuest();
    }

    public function test_empty_password_does_not_call_google(): void
    {
        Http::fake();

        $this->put('login', ['username' => 'admin@pps.test', 'password' => '', 'g-recaptcha-response' => 'token-abc'])
            ->assertJson(['success' => false]);
        Http::assertNothingSent();
    }

    public function test_min_score_is_clamped_between_zero_and_one(): void
    {
        foreach (['5' => 1.0, '-1' => 0.0, '0.7' => 0.7] as $env => $expected) {
            $_SERVER['RECAPTCHA_MIN_SCORE'] = $_ENV['RECAPTCHA_MIN_SCORE'] = (string) $env;
            putenv('RECAPTCHA_MIN_SCORE='.$env);

            $this->assertSame($expected, (require config_path('services.php'))['recaptcha']['min_score']);
        }

        unset($_SERVER['RECAPTCHA_MIN_SCORE'], $_ENV['RECAPTCHA_MIN_SCORE']);
        putenv('RECAPTCHA_MIN_SCORE');
    }
}
