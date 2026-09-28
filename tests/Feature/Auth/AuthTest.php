<?php

namespace Tests\Feature\Auth;

use App\Models\LokasiProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'admin', ?LokasiProgram $lokasi = null): User
    {
        return User::factory()->role($role)->create([
            'email' => $role.'@pps.test',
            'password' => Hash::make('Rahasia123'),
            'location_program' => $lokasi?->id,
        ]);
    }

    public function test_admin_can_login_and_session_is_regenerated(): void
    {
        $this->makeUser();
        $this->get('login');
        $oldSession = session()->getId();

        $this->put('login', ['username' => 'admin@pps.test', 'password' => 'Rahasia123'])
            ->assertJson(['success' => true, 'redirect_url' => url('home')]);

        $this->assertAuthenticated();
        $this->assertNotSame($oldSession, session()->getId());
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->makeUser();

        $this->put('login', ['username' => 'admin@pps.test', 'password' => 'salah'])
            ->assertJson(['success' => false, 'messages' => 'Email atau Password Salah']);
        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_failures(): void
    {
        $this->makeUser();

        foreach (range(1, 5) as $i) {
            $this->put('login', ['username' => 'admin@pps.test', 'password' => 'salah']);
        }

        $this->put('login', ['username' => 'admin@pps.test', 'password' => 'Rahasia123'])
            ->assertJson(['success' => false])
            ->assertJsonPath('messages', fn ($m) => str_contains($m, 'Terlalu banyak percobaan'));
        $this->assertGuest();
    }

    public function test_mahasiswa_must_choose_own_lokasi(): void
    {
        $lokasi = LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Bandung']);
        LokasiProgram::factory()->create(['nama_lokasi' => 'Kota Cimahi']);
        $this->makeUser('mahasiswa', $lokasi);

        $this->put('login', ['username' => 'mahasiswa@pps.test', 'password' => 'Rahasia123', 'lokasi' => 'Kota Cimahi'])
            ->assertJson(['success' => false, 'messages' => 'Lokasi program anda tidak valid!']);
        $this->assertGuest();

        $this->put('login', ['username' => 'mahasiswa@pps.test', 'password' => 'Rahasia123', 'lokasi' => 'kota bandung'])
            ->assertJson(['success' => true, 'redirect_url' => url('home/kota%20bandung')]);
        $this->assertSame('Kota Bandung', session('lokasi_program'));
    }

    public function test_logout_requires_post_and_invalidates_session(): void
    {
        $this->actingAs($this->makeUser());

        $this->get('logout')->assertStatus(405);
        $this->post('logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_password_change_enforces_rules(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);

        $this->put('setting/update', ['plama' => 'salah', 'pbaru' => 'Baru12345', 'pbaruulangi' => 'Baru12345'])
            ->assertJsonPath('errors.plama.0', 'Password lama salah!');
        $this->put('setting/update', ['plama' => 'Rahasia123', 'pbaru' => 'pendek', 'pbaruulangi' => 'pendek'])
            ->assertJsonValidationErrors('pbaru', 'errors');

        $this->put('setting/update', ['plama' => 'Rahasia123', 'pbaru' => 'Baru12345', 'pbaruulangi' => 'Baru12345'])
            ->assertJson(['success' => true]);
        $this->assertTrue(Hash::check('Baru12345', $user->fresh()->password));
    }
}
