<?php

namespace Tests\Feature;

use App\Models\Finca;
use App\Models\Personal;
use App\Models\User;
use App\Models\UsuarioVeterinario;
use Illuminate\Database\Eloquent\Collection;
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


        $this->userAdmin
            = User::factory()
            ->hasConfiguracion()
            ->create(['usuario' => 'admin', 'password' => Hash::make('admin')]);

            $this->userAdmin->assignRole('admin');

            $this->finca
            = Finca::factory()
            ->for($this->userAdmin)
            ->create();

        $this->userVeterinario
            = User::factory()
            ->create(['usuario' => 'veterinario', 'password' => Hash::make('veterinario')]);

            UsuarioVeterinario::factory()
            ->for(Personal::factory()->for($this->finca)->create(['cargo_id' => 2]), 'veterinario')
            ->create(['admin_id' => $this->userAdmin->id,
            'user_id'=>$this->userVeterinario->id]);

            $this->userVeterinario->assignRole('veterinario');

    }

    private function generarFincas(): Collection
    {
        return Finca::factory()
            ->count(10)
            ->for($this->userAdmin)
            ->create();
    }

    /**
     * A basic feature test example.
     */
     public function test_logear_usuario_admin_tiene_una_finca(): void
    {
        $response = $this->withHeader('origin', config('app.url'))->postJson('api/login', [
            'usuario' => 'admin',
            'password' => 'admin',
        ]);

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->has('login',fn(AssertableJson $json)=>
            $json->where('rol', 'admin')->whereAllType([
            'id' => 'integer',
            'usuario' => 'string',
            'token' => 'string',
            'configuracion.peso_servicio' => 'integer',
            'configuracion.dias_evento_notificacion' => 'integer',
            'configuracion.dias_diferencia_vacuna' => 'integer',
        ])->where('sesion_finca', true))

        )->assertSessionHas('finca_id', $this->finca->id)
        ->assertSessionHas('peso_servicio',$this->userAdmin->configuracion->peso_servicio)
        ->assertSessionHas('dias_evento_notificacion',$this->userAdmin->configuracion->dias_evento_notificacion)
        ->assertSessionHas('dias_diferencia_vacuna',$this->userAdmin->configuracion->dias_diferencia_vacuna);
    }


    public function test_logear_usuario_admin_tiene_varias_fincas(): void
    {
      $this->generarFincas();

        $response = $this->withHeader('origin', config('app.url'))->postJson('api/login', [
            'usuario' => 'admin',
            'password' => 'admin',
        ]);

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->has('login',fn(AssertableJson $json)=>
            $json->where('rol', 'admin')->whereAllType([
            'id' => 'integer',
            'usuario' => 'string',
            'token' => 'string',
            'configuracion.peso_servicio' => 'integer',
            'configuracion.dias_evento_notificacion' => 'integer',
            'configuracion.dias_diferencia_vacuna' => 'integer',
        ])->where('sesion_finca', false))
    )->assertSessionMissing('finca_id', null)
    ->assertSessionHas('peso_servicio',$this->userAdmin->configuracion->peso_servicio)
    ->assertSessionHas('dias_evento_notificacion',$this->userAdmin->configuracion->dias_evento_notificacion)
    ->assertSessionHas('dias_diferencia_vacuna',$this->userAdmin->configuracion->dias_diferencia_vacuna);
    }


    public function test_logear_usuario_veterinario_con_admin_tiene_varias_fincas(): void
    {
        $this->generarFincas();
        $response = $this->withHeader('origin', config('app.url'))->postJson('api/login', [
            'usuario' => 'veterinario',
            'password' => 'veterinario',
        ]);

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->has('login',fn(AssertableJson $json)=>
            $json->where('rol', 'veterinario')->whereAllType([
            'id' => 'integer',
            'usuario' => 'string',
            'token' => 'string',
            'configuracion.peso_servicio' => 'integer',
            'configuracion.dias_evento_notificacion' => 'integer',
            'configuracion.dias_diferencia_vacuna' => 'integer',
        ])->where('sesion_finca', true))
            ->where('login.sesion_finca', true)
    )->assertSessionHas('finca_id', $this->finca->id)
    ->assertSessionHas('peso_servicio',$this->userAdmin->configuracion->peso_servicio)
    ->assertSessionHas('dias_evento_notificacion',$this->userAdmin->configuracion->dias_evento_notificacion)
    ->assertSessionHas('dias_diferencia_vacuna',$this->userAdmin->configuracion->dias_diferencia_vacuna);
    }

    public function test_logear_usuario_veterinario_con_admin_tiene_una_finca(): void
    {
        $response = $this->withHeader('origin', config('app.url'))->postJson('api/login', [
            'usuario' => 'veterinario',
            'password' => 'veterinario',
        ]);

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->has('login',fn(AssertableJson $json)=>
        $json->where('rol', 'veterinario')->whereAllType([
        'id' => 'integer',
        'usuario' => 'string',
        'token' => 'string',
        'configuracion.peso_servicio' => 'integer',
        'configuracion.dias_evento_notificacion' => 'integer',
        'configuracion.dias_diferencia_vacuna' => 'integer',
    ])->where('sesion_finca', true))
    )->assertSessionHas('finca_id', $this->finca->id)
    ->assertSessionHas('peso_servicio',$this->userAdmin->configuracion->peso_servicio)
    ->assertSessionHas('dias_evento_notificacion',$this->userAdmin->configuracion->dias_evento_notificacion)
    ->assertSessionHas('dias_diferencia_vacuna',$this->userAdmin->configuracion->dias_diferencia_vacuna);
    }
}
