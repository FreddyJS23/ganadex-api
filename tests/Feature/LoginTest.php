<?php

namespace Tests\Feature;

use App\Models\Finca;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private $userAdmin;
    private $userVeterinario;
    private $finca;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finca
        = Finca::factory()
        ->create();

        $this->userAdmin
            = User::factory()
            ->hasAttached($this->finca)
            ->create(['usuario' => 'admin', 'password' => Hash::make('admin')]);

            $this->userAdmin->assignRole('admin');

        $this->userVeterinario
            = User::factory()
            ->hasAttached($this->finca)
            ->create(['usuario' => 'veterinario', 'password' => Hash::make('veterinario')]);

            $this->userVeterinario->assignRole('veterinario');

    }

    /**
     * A basic feature test example.
     */
    public function test_logear_usuario_admin(): void
    {
        $response = $this->withHeader('origin', config('app.url'))->postJson('api/login', [
            'usuario' => 'admin',
            'password' => 'admin',
        ]);

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->where('login.rol', 'admin')->whereAllType([
            'login.id' => 'integer',
            'login.usuario' => 'string',
            'login.token' => 'string',
            'login.finca'=>'integer'
        ]));
    }

    public function test_logear_usuario_veterinario(): void
    {
        $response = $this->withHeader('origin', config('app.url'))->postJson('api/login', [
            'usuario' => 'veterinario',
            'password' => 'veterinario',
        ]);

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->where('login.rol', 'veterinario')->whereAllType([
            'login.id' => 'integer',
            'login.usuario' => 'string',
            'login.token' => 'string',
            'login.finca'=>'integer'
        ]));
    }
}
