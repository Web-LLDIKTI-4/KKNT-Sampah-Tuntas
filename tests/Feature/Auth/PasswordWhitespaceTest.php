<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordWhitespaceTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(string $password): User
    {
        return User::factory()->role('admin')->create([
            'email' => 'admin@pps.test',
            'password' => Hash::make($password),
        ]);
    }

    private function changePassword(string $old, string $new)
    {
        return $this->putJson('setting/update', ['plama' => $old, 'pbaru' => $new, 'pbaruulangi' => $new]);
    }

    // Password change must already have logged the current session out
    private function assertCanLogin(string $password): void
    {
        $this->assertGuest();

        $this->put('login', ['username' => 'admin@pps.test', 'password' => $password])
            ->assertJson(['success' => true]);
        $this->assertAuthenticated();
    }

    public function test_new_password_with_surrounding_spaces_is_stored_verbatim_and_can_login(): void
    {
        $user = $this->makeAdmin('Rahasia123');
        $this->actingAs($user);

        $this->changePassword('Rahasia123', ' Baru12345 ')->assertJson(['success' => true]);

        $hash = $user->fresh()->password;
        $this->assertTrue(Hash::check(' Baru12345 ', $hash));
        $this->assertFalse(Hash::check('Baru12345', $hash));

        $this->assertCanLogin(' Baru12345 ');
    }

    public function test_trimmed_variant_cannot_login_after_spaced_password_change(): void
    {
        $user = $this->makeAdmin('Rahasia123');
        $this->actingAs($user);
        $this->changePassword('Rahasia123', ' Baru12345 ')->assertJson(['success' => true]);

        $this->assertGuest();
        $this->put('login', ['username' => 'admin@pps.test', 'password' => 'Baru12345'])
            ->assertJson(['success' => false]);
        $this->assertGuest();
    }

    public function test_current_password_with_surrounding_spaces_is_not_trimmed(): void
    {
        $user = $this->makeAdmin(' Rahasia123 ');
        $this->actingAs($user);

        // Trimmed old password must be rejected
        $this->changePassword('Rahasia123', 'Baru12345')
            ->assertJsonPath('errors.plama.0', 'Kata sandi lama salah!');

        $this->changePassword(' Rahasia123 ', 'Baru12345')->assertJson(['success' => true]);
        $this->assertTrue(Hash::check('Baru12345', $user->fresh()->password));
        $this->assertCanLogin('Baru12345');
    }

    public function test_confirmation_differing_only_by_whitespace_is_rejected(): void
    {
        $user = $this->makeAdmin('Rahasia123');
        $this->actingAs($user);

        $this->putJson('setting/update', ['plama' => 'Rahasia123', 'pbaru' => ' Baru12345 ', 'pbaruulangi' => 'Baru12345'])
            ->assertJsonValidationErrors('pbaruulangi', 'errors');
        $this->assertTrue(Hash::check('Rahasia123', $user->fresh()->password));
    }

    public function test_normal_password_change_and_login_still_work(): void
    {
        $user = $this->makeAdmin('Rahasia123');
        $this->actingAs($user);

        $this->changePassword('Rahasia123', 'Baru12345')
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => 'Kata sandi berhasil diubah. Silakan login kembali.',
                'redirect' => route('login'),
            ]);
        $this->assertTrue(Hash::check('Baru12345', $user->fresh()->password));
        $this->assertCanLogin('Baru12345');
    }

    public function test_non_ajax_password_change_redirects_to_login_as_guest(): void
    {
        $user = $this->makeAdmin('Rahasia123');
        $this->actingAs($user);

        $this->put('setting/update', ['plama' => 'Rahasia123', 'pbaru' => 'Baru12345', 'pbaruulangi' => 'Baru12345'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', 'Kata sandi berhasil diubah. Silakan login kembali.');
        $this->assertGuest();
        $this->assertCanLogin('Baru12345');
    }

    public function test_guest_cannot_change_password(): void
    {
        $this->makeAdmin('Rahasia123');

        $this->put('setting/update', ['plama' => 'Rahasia123', 'pbaru' => 'Baru12345', 'pbaruulangi' => 'Baru12345'])
            ->assertRedirect(route('login'));
    }

    public function test_other_fields_are_still_trimmed(): void
    {
        $request = Request::create('/setting/update', 'PUT', [
            'name' => '  Budi  ',
            'pbaru' => ' Baru12345 ',
        ]);

        $handled = app(TrimStrings::class)->handle($request, fn (Request $r) => $r);

        $this->assertSame('Budi', $handled->input('name'));
        $this->assertSame(' Baru12345 ', $handled->input('pbaru'));
    }
}
