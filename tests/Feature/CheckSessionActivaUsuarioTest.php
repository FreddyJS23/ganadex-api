<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CheckSessionActivaUsuarioTest extends TestCase
{
    use RefreshDatabase;
    
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();
    }
    
 
    public function test_comprobar_tiene_sesion_activa(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('checkSession'));

        $response->assertStatus(200);
    }
    
    public function test_no_tiene_sesion_activa(): void
    {
        $response = $this->getJson(route('checkSession'));

        $response->assertStatus(401);
    }
}
