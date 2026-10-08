<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AkunPimpinanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// Akun admin, kepala, pemda (pengganti AdminSeeder)
class AkunPimpinanSeederTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = 'admin.seed@kknt.test';

    private function seedAdmin(?string $password = 'password-aman-123', ?string $email = self::EMAIL): void
    {
        config(['app.seed_password' => $password, 'app.seed_admin_email' => $email]);
        (new AkunPimpinanSeeder)->run();
    }

    private function asProduction(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
    }

    public function test_creates_single_admin(): void
    {
        $this->seedAdmin();

        $this->assertSame('admin', User::where('email', self::EMAIL)->value('role'));
        $this->assertSame(1, User::where('role', 'admin')->count());
    }

    public function test_rerun_resets_password_without_duplicate(): void
    {
        $this->seedAdmin('password-pertama-1');
        $this->seedAdmin('password-kedua-22');

        $this->assertTrue(Hash::check('password-kedua-22', User::where('email', self::EMAIL)->value('password')));
        $this->assertSame(1, User::where('email', self::EMAIL)->count());
    }

    public function test_defaults_to_fallback_email_and_password(): void
    {
        $this->seedAdmin(null, null);

        $admin = User::where('role', 'admin')->where('email', 'admin@kknt.test')->first();
        $this->assertTrue(Hash::check('password', $admin->password));
    }

    public function test_promotes_existing_email_to_admin(): void
    {
        $mhs = User::factory()->role('mahasiswa')->create(['email' => self::EMAIL]);

        $this->seedAdmin();

        $this->assertSame('admin', $mhs->fresh()->role);
        $this->assertSame(1, User::where('email', self::EMAIL)->count());
    }

    public function test_accepts_short_password_and_runs_in_production(): void
    {
        $this->asProduction();

        $this->seedAdmin('pendek');

        $this->assertTrue(Hash::check('pendek', User::where('email', self::EMAIL)->value('password')));
    }

    public function test_creates_kepala_and_pemda_once_with_seed_password(): void
    {
        $this->seedAdmin('password-aman-123');
        $this->seedAdmin('password-aman-123');

        foreach (['kepala' => 'kepala@kknt.test', 'pemda' => 'pemda@kknt.test'] as $role => $email) {
            $this->assertSame(1, User::where('role', $role)->count(), $role);
            $this->assertSame($role, User::where('email', $email)->value('role'));
            $this->assertTrue(Hash::check('password-aman-123', User::where('email', $email)->value('password')));
        }
    }

    public function test_does_not_touch_non_seed_accounts(): void
    {
        $asli = User::factory()->role('kepala')->create(['email' => 'kepala@lldikti.go.id', 'password' => Hash::make('rahasia-asli')]);

        $this->seedAdmin();

        $this->assertTrue(Hash::check('rahasia-asli', $asli->fresh()->password));
        $this->assertSame('kepala', $asli->fresh()->role);
    }
}
