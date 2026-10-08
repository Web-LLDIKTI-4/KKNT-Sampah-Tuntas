<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Concerns\SeedsDummyData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Akun admin (SEED_ADMIN_EMAIL, password di-reset tiap jalan), kepala & pemda (@kknt.test, dibuat bila belum ada).
 * Jalankan: php artisan db:seed --class=AkunPimpinanSeeder
 * Prasyarat: -
 */
class AkunPimpinanSeeder extends Seeder
{
    use SeedsDummyData;

    public function run(): void
    {
        $email = config('app.seed_admin_email') ?: 'admin'.self::DOMAIN;

        // role tidak ada di $fillable; email yang sudah ada dipromosikan jadi admin
        Model::unguarded(fn () => User::updateOrCreate(['email' => $email], [
            'name' => 'Administrator',
            'role' => 'admin',
            'password' => Hash::make($this->password()),
            'email_verified_at' => now(),
        ]));
        $this->akun[] = ['admin', $email, 'Administrator', 'Password di-reset'];

        // $this->user('kepala', 'kepala'.self::DOMAIN, 'Kepala LLDIKTI');
        // $this->user('pemda', 'pemda'.self::DOMAIN, 'Pemerintah Daerah');

        $this->tampilkanAkun();
    }
}
