<?php

namespace Tests\Feature;

use Tests\Feature\Common\NeedsSetupRequest;
use Tests\TestCase;

class CheckSessionActivaUsuarioTest extends TestCase
{
    use NeedsSetupRequest{
        setUp as needsSetupRequestSetUp;
    }

    protected function setUp(): void
    {
        $this->needsSetupRequestSetUp();
    }


    public function test_comprobar_tiene_sesion_activa(): void
    {
        $this
            ->setUpRequest()
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
