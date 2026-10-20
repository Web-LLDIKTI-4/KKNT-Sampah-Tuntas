<?php

namespace Tests\Feature\Admin;

use App\Models\Dpl;
use App\Models\Mahasiswa;
use App\Models\Satuanpendidikan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// Tambah Pengguna DPL/mahasiswa: no email format check, only uniqueness (user decision)
class CreateAccountsNonStandardEmailTest extends TestCase
{
    use RefreshDatabase;

    private const DPL_EMAIL = 'salmasuroyay.@gmail.com';

    private const MHS_EMAIL = 'eggiseptiantos.t.@gmail.com';

    private Satuanpendidikan $sp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sp = Satuanpendidikan::factory()->create();
    }

    public function test_dpl_with_non_standard_email_gets_account_and_is_matched(): void
    {
        $this->loginAs('admin');
        $dpl = $this->dpl(['email' => self::DPL_EMAIL]);

        $this->put('user/insertuser', ['createuser' => [self::DPL_EMAIL]])
            ->assertOk()->assertJsonMissingPath('error')->assertJson(['success' => '1 user berhasil dibuat']);

        $user = Dpl::find($dpl->id_dpl)->user;
        $this->assertNotNull($user);
        $this->assertSame(self::DPL_EMAIL, $user->email);
        $this->assertSame('dpl', $user->role);
        $this->assertTrue(Hash::check($dpl->nidn, $user->password));
    }

    public function test_mahasiswa_with_non_standard_email_gets_account_and_is_matched(): void
    {
        $this->loginAs('admin');
        $mhs = $this->mahasiswa(['email' => self::MHS_EMAIL]);

        $this->put('user/insert', ['createuser' => [self::MHS_EMAIL]])
            ->assertOk()->assertJsonMissingPath('error')->assertJson(['success' => '1 user berhasil dibuat']);

        $user = Mahasiswa::find($mhs->id_mahasiswa)->user;
        $this->assertNotNull($user);
        $this->assertSame(self::MHS_EMAIL, $user->email);
        $this->assertSame('mahasiswa', $user->role);
        $this->assertTrue(Hash::check($mhs->nim, $user->password));
    }

    public function test_mixed_batch_of_standard_and_non_standard_emails(): void
    {
        $this->loginAs('admin');
        $odd = $this->mahasiswa(['email' => self::MHS_EMAIL]);
        $normal = $this->mahasiswa();

        $this->put('user/insert', ['createuser' => [self::MHS_EMAIL, $normal->email]])
            ->assertOk()->assertJson(['success' => '2 user berhasil dibuat']);

        $this->assertNotNull(Mahasiswa::find($odd->id_mahasiswa)->user);
        $this->assertNotNull(Mahasiswa::find($normal->id_mahasiswa)->user);
    }

    public function test_email_with_existing_account_does_not_create_duplicate(): void
    {
        $this->loginAs('admin');
        $this->dpl(['email' => self::DPL_EMAIL]);
        $this->mahasiswa(['email' => self::MHS_EMAIL]);
        User::factory()->role('dpl')->create(['email' => self::DPL_EMAIL]);
        User::factory()->role('mahasiswa')->create(['email' => self::MHS_EMAIL]);

        foreach ([self::DPL_EMAIL, strtoupper(self::DPL_EMAIL), ' '.self::DPL_EMAIL.' '] as $variant) {
            $this->put('user/insertuser', ['createuser' => [$variant]])
                ->assertOk()->assertJson(['success' => '0 user berhasil dibuat']);
        }
        foreach ([self::MHS_EMAIL, strtoupper(self::MHS_EMAIL)] as $variant) {
            $this->put('user/insert', ['createuser' => [$variant]])
                ->assertOk()->assertJson(['success' => '0 user berhasil dibuat']);
        }

        $this->assertSame(1, User::where('email', self::DPL_EMAIL)->count());
        $this->assertSame(1, User::where('email', self::MHS_EMAIL)->count());
    }

    public function test_repeated_submit_creates_account_only_once(): void
    {
        $this->loginAs('admin');
        $this->dpl(['email' => self::DPL_EMAIL]);

        $this->put('user/insertuser', ['createuser' => [self::DPL_EMAIL]])->assertJson(['success' => '1 user berhasil dibuat']);
        $this->put('user/insertuser', ['createuser' => [self::DPL_EMAIL]])->assertJson(['success' => '0 user berhasil dibuat']);

        $this->assertSame(1, User::where('email', self::DPL_EMAIL)->count());
    }

    public function test_duplicates_within_one_batch_create_single_account(): void
    {
        $this->loginAs('admin');
        $this->dpl(['email' => self::DPL_EMAIL]);
        $this->mahasiswa(['email' => self::MHS_EMAIL]);

        $this->put('user/insertuser', ['createuser' => [
            self::DPL_EMAIL, self::DPL_EMAIL, strtoupper(self::DPL_EMAIL), '  '.self::DPL_EMAIL,
        ]])->assertOk()->assertJsonMissingPath('error')->assertJson(['success' => '1 user berhasil dibuat']);

        $this->put('user/insert', ['createuser' => [
            self::MHS_EMAIL, self::MHS_EMAIL.' ', 'EggiSeptiantos.T.@Gmail.com',
        ]])->assertOk()->assertJsonMissingPath('error')->assertJson(['success' => '1 user berhasil dibuat']);

        $this->assertSame(1, User::where('email', self::DPL_EMAIL)->count());
        $this->assertSame(1, User::where('email', self::MHS_EMAIL)->count());
    }

    public function test_empty_email_is_still_rejected(): void
    {
        $this->loginAs('admin');
        // Source records with empty email must never be turned into accounts
        $this->dpl(['email' => '']);
        $this->mahasiswa(['email' => '']);

        foreach (['user/insert', 'user/insertuser'] as $uri) {
            foreach ([[], ['createuser' => []], ['createuser' => ['']], ['createuser' => ['   ', '']], ['createuser' => [null]]] as $payload) {
                $this->put($uri, $payload)->assertOk()->assertJson(['error' => 'user harus dipilih']);
            }
        }

        $this->assertSame(0, User::whereIn('role', ['mahasiswa', 'dpl'])->count());
    }

    public function test_non_standard_email_still_rejected_when_not_in_source_table(): void
    {
        $this->loginAs('admin');

        $this->put('user/insertuser', ['createuser' => [self::DPL_EMAIL]])
            ->assertOk()->assertJsonPath('error', fn ($error) => str_contains($error, self::DPL_EMAIL));
        $this->put('user/insert', ['createuser' => [self::MHS_EMAIL]])
            ->assertOk()->assertJsonPath('error', fn ($error) => str_contains($error, self::MHS_EMAIL));

        $this->assertSame(0, User::whereIn('role', ['mahasiswa', 'dpl'])->count());
    }

    public function test_non_admin_cannot_create_accounts_with_non_standard_email(): void
    {
        $this->dpl(['email' => self::DPL_EMAIL]);
        $this->mahasiswa(['email' => self::MHS_EMAIL]);

        foreach (['dpl', 'mahasiswa', 'kepala', 'pemda'] as $role) {
            $this->loginAs($role);
            foreach (['user/insertuser' => self::DPL_EMAIL, 'user/insert' => self::MHS_EMAIL] as $uri => $email) {
                $response = $this->put($uri, ['createuser' => [$email]]);
                $this->assertTrue(
                    $response->status() === 403 || $response->isRedirect(route('home')),
                    "{$role} {$uri}: expected 403 or redirect home, got {$response->status()}"
                );
            }
        }

        $this->assertSame(0, User::whereIn('email', [self::DPL_EMAIL, self::MHS_EMAIL])->count());
    }

    private function mahasiswa(array $attributes = []): Mahasiswa
    {
        return Mahasiswa::factory()->create($attributes + ['kodept' => $this->sp->npsn]);
    }

    private function dpl(array $attributes = []): Dpl
    {
        return Dpl::factory()->create($attributes + ['kodept' => $this->sp->npsn]);
    }
}
