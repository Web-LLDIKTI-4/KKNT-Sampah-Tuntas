<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Seeder dummy tidak boleh dijalankan di production.');
        }

        $password = config('app.seed_password') ?: Str::password(12, symbols: false);

        $this->call(MasterDataSeeder::class);
        $this->callWith(UserSeeder::class, ['password' => $password]);
        $this->call(ActivitySeeder::class);

        $this->command->warn('Password semua akun dummy: '.$password);
    }
}
