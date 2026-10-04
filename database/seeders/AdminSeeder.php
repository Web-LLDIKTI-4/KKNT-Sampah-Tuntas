<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('app.seed_admin_email') ?: 'admin@kknt.test';

        // role tidak ada di $fillable
        Model::unguarded(fn () => User::updateOrCreate(['email' => $email], [
            'name' => 'Administrator',
            'role' => 'admin',
            'password' => Hash::make(config('app.seed_password') ?: 'password'),
            'email_verified_at' => now(),
        ]));

        $this->command?->info("Admin {$email} siap (password di-reset).");
    }
}
