<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordChangeSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_change_logs_out_current_and_other_sessions(): void
    {
        $this->withoutVite();
        $user = User::factory()->role('admin')->create(['password' => Hash::make('Rahasia123')]);

        // Session without stored hash (pre-deploy session) must not be logged out
        $this->actingAs($user)->get('setting')->assertOk();
        $otherDeviceHash = session('password_hash_web');
        $this->assertNotNull($otherDeviceHash);

        $this->putJson('setting/update', ['plama' => 'Rahasia123', 'pbaru' => 'Baru12345', 'pbaruulangi' => 'Baru12345'])
            ->assertOk()
            ->assertJson(['success' => true, 'redirect' => route('login')]);

        // Current session is logged out
        $this->assertGuest();
        $this->get('setting')->assertRedirect(route('login'));

        // Other device: session still carries the old password hash
        $this->flushSession();
        $this->actingAs($user->fresh())
            ->withSession(['password_hash_web' => $otherDeviceHash])
            ->get('setting')
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
