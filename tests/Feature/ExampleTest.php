<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_is_public_landing_with_login_link(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertViewIs('landing')
            ->assertSee('href="'.route('login').'"', false);
    }
}
