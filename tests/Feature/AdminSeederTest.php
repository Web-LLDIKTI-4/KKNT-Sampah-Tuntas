<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\BebanSeeder;
use Database\Seeders\SimulasiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AdminSeederTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = 'admin.seed@kknt.test';

    private function seedAdmin(?string $password = 'password-aman-123', ?string $email = self::EMAIL): void
    {
        config(['app.seed_password' => $password, 'app.seed_admin_email' => $email]);
        (new AdminSeeder)->run();
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

    public function test_rerun_does_not_change_password_or_duplicate(): void
    {
        $this->seedAdmin('password-pertama-1');
        $hash = User::where('email', self::EMAIL)->value('password');

        $this->seedAdmin('password-kedua-22');

        $this->assertSame($hash, User::where('email', self::EMAIL)->value('password'));
        $this->assertSame(1, User::where('email', self::EMAIL)->count());
    }

    public function test_empty_password_outside_production_generates_one_without_command(): void
    {
        $this->seedAdmin(null, null);

        $this->assertSame(1, User::where('role', 'admin')->where('email', 'admin@kknt.test')->count());
    }

    public function test_does_not_promote_existing_non_admin_email(): void
    {
        $mhs = User::factory()->role('mahasiswa')->create(['email' => self::EMAIL]);

        $this->assertThrows(fn () => $this->seedAdmin(), RuntimeException::class);
        $this->assertSame('mahasiswa', $mhs->fresh()->role);
    }

    public function test_rejects_short_password(): void
    {
        $this->assertThrows(fn () => $this->seedAdmin('pendek'), RuntimeException::class);
        $this->assertSame(0, User::where('role', 'admin')->count());
    }

    public function test_production_requires_email_and_password(): void
    {
        $this->asProduction();

        $this->assertThrows(fn () => $this->seedAdmin('password-aman-123', null), RuntimeException::class);
        $this->assertThrows(fn () => $this->seedAdmin(null), RuntimeException::class);
        $this->assertSame(0, User::count());
    }

    public function test_dummy_seeders_refuse_production(): void
    {
        $this->asProduction();

        $this->assertThrows(fn () => (new SimulasiSeeder)->run('password-aman-123'), RuntimeException::class);
        $this->assertThrows(fn () => (new BebanSeeder)->run(), RuntimeException::class);
    }
}
