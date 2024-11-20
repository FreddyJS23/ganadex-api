<?php

namespace Tests\Feature;

use App\Models\Finca;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $finca;

    protected function setUp(): void
    {
        parent::setUp();



        $this->user
            = User::factory()->create();

            $this->finca
            = Finca::factory()
            ->for($this->user)
            ->create();


    }

    /**
     * A basic feature test example.
     */
    public function test_logear_usuario(): void
    {

        $response = $this->withHeader('origin', config('app.url'))->postJson('api/login', [
            'usuario' => 'admin',
            'password' => 'admin',

        ]);


        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->whereAllType([
            'login.id' => 'integer',
            'login.usuario' => 'string',
            'login.token' => 'string'
        ]));
    }
}
