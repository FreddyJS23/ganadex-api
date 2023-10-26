<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class LoginTest extends TestCase
{

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();
    }

    /**
     * A basic feature test example.
     */
    public function test_logear_usuario(): void
    {
        $response = $this->postJson('api/login', ['usuario' =>'admin', 'password' =>'admin']);

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) => 
        $json->whereAllType([
            'login.id' => 'integer', 
            'login.usuario' => 'string', 
            'login.token' => 'string']));
    }
}
