<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Verifikasi token reCAPTCHA v3 ke Google (fail-closed).
 */
class RecaptchaService
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    // Hanya RECAPTCHA_ENABLED=false yang mematikan; key kosong di production tetap aktif (fail-closed)
    public function enabled(): bool
    {
        if (! config('services.recaptcha.enabled', true)) {
            return false;
        }

        if ($this->configured()) {
            return true;
        }

        // Misconfig production: login pasti ditolak; log maksimal 1x per menit
        if (app()->isProduction() && Cache::add('recaptcha:misconfig-logged', true, 60)) {
            Log::error('reCAPTCHA aktif tapi RECAPTCHA_SITE_KEY/RECAPTCHA_SECRET_KEY kosong, login ditolak');
        }

        return app()->isProduction();
    }

    public function configured(): bool
    {
        return filled(config('services.recaptcha.site_key')) && filled(config('services.recaptcha.secret_key'));
    }

    // Site key untuk view; null bila script tidak perlu dimuat
    public function siteKey(): ?string
    {
        return $this->enabled() && $this->configured() ? config('services.recaptcha.site_key') : null;
    }

    public function verify(?string $token, string $action, ?string $ip): bool
    {
        if (blank($token)) {
            return false;
        }

        if (! $this->configured()) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->connectTimeout(max(1, (int) config('services.recaptcha.connect_timeout', 2)))
                ->timeout(max(1, (int) config('services.recaptcha.timeout', 3)))
                ->post(self::VERIFY_URL, [
                    'secret' => config('services.recaptcha.secret_key'),
                    'response' => $token,
                    'remoteip' => $ip,
                ]);
        } catch (Throwable $e) {
            Log::warning('reCAPTCHA tidak terjangkau', ['error' => $e->getMessage()]);

            return false;
        }

        if (! $response->successful()) {
            Log::warning('reCAPTCHA HTTP error', ['status' => $response->status()]);

            return false;
        }

        $score = (float) $response->json('score', 0);
        $hostname = config('services.recaptcha.hostname');
        $passed = $response->json('success') === true
            && $response->json('action') === $action
            && $score >= (float) config('services.recaptcha.min_score', 0.5)
            && (blank($hostname) || strcasecmp((string) $response->json('hostname'), $hostname) === 0);

        if (! $passed) {
            Log::info('reCAPTCHA ditolak', [
                'score' => $response->json('score'),
                'action' => $response->json('action'),
                'hostname' => $response->json('hostname'),
                'errors' => $response->json('error-codes'),
            ]);
        }

        return $passed;
    }
}
