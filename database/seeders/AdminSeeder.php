<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class AdminSeeder extends Seeder
{
    private const MIN_PASSWORD = 12;

    public function run(): void
    {
        $email = config('app.seed_admin_email');
        if (! $email) {
            if (app()->isProduction()) {
                throw new RuntimeException('SEED_ADMIN_EMAIL wajib diisi di production.');
            }
            $email = 'admin@kknt.test';
        }

        $existing = User::where('email', $email)->first();
        if ($existing) {
            if ($existing->role !== 'admin') {
                throw new RuntimeException("Email {$email} sudah dipakai akun role {$existing->role}; tidak di-promote ke admin.");
            }
            $this->command?->info("Admin {$email} sudah ada, tidak diubah.");

            return;
        }

        $password = $this->password();

        // role tidak ada di $fillable
        Model::unguarded(fn () => User::create([
            'email' => $email,
            'name' => 'Administrator',
            'role' => 'admin',
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]));

        if (config('app.seed_password')) {
            $this->command?->warn("Admin {$email} dibuat dengan SEED_DEFAULT_PASSWORD. Segera ganti password setelah login.");
        } else {
            $this->command?->warn("Admin {$email} dibuat. Password: {$password} (hanya tampil sekali, segera ganti setelah login).");
        }
    }

    private function password(): string
    {
        $password = (string) config('app.seed_password');

        if ($password === '') {
            if (app()->isProduction()) {
                throw new RuntimeException('SEED_DEFAULT_PASSWORD wajib diisi di production.');
            }

            return Str::password(self::MIN_PASSWORD, symbols: false);
        }

        if (strlen($password) < self::MIN_PASSWORD) {
            throw new RuntimeException('SEED_DEFAULT_PASSWORD minimal '.self::MIN_PASSWORD.' karakter.');
        }

        return $password;
    }
}
