<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Common\NeedsUser;
use Tests\TestCase;

class CheckSessionActivaUsuarioTest extends TestCase
{
    use RefreshDatabase;
    use NeedsUser;

    public function test_comprobar_tiene_sesion_activa(): void
    {
        $this
            ->actingAs($this->user)
            ->getJson(route('checkSession'))
            ->assertStatus(200);
    }

    public function test_no_tiene_sesion_activa(): void
    {
        $this
            ->getJson(route('checkSession'))
            ->assertStatus(401);
    }
}
