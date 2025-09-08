<?php

namespace Tests\Feature;

use App\Models\Personal;
use App\Models\User;
use App\Models\UsuarioVeterinario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\Common\NeedsHacienda;
use Tests\Feature\Common\NeedsPersonal;
use Tests\Feature\Common\NeedsSetupRequest;
use Tests\Feature\Common\NeedsUsuarioVeterinario;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use NeedsSetupRequest,
    NeedsPersonal,
    NeedsHacienda,
    NeedsUsuarioVeterinario
    {
    NeedsSetupRequest::setUp as needsSetupRequestSetUp;
    NeedsUsuarioVeterinario::setUp as needsUsuarioVeterinarioSetUp;
    }


    private $userAdmin;

    protected function setUp(): void
    {

        $this->needsSetupRequestSetUp();
        $this->needsUsuarioVeterinarioSetUp();

        $this->userAdmin=$this->user;

    }

    private function userVeterinarioEnVariasHaciendas(): User
    {
        $haciendas=$this->generarHaciendas();
        $veterinario
        = User::factory()
        ->create(['usuario' => 'veterinario2', 'password' => Hash::make('veterinario2')]);

        $userVeterinario=UsuarioVeterinario::factory()
        ->for(Personal::factory()->for($this->userAdmin)->hasAttached($haciendas)->create(['cargo_id' => 2]), 'veterinario')
        ->create(['admin_id' => $this->userAdmin->id,
        'user_id' => $veterinario->id]);

        $veterinario->assignRole('veterinario');

        return $veterinario;
    }

    /**
     * A basic feature test example.
     */
    public function test_logear_usuario_admin_tiene_una_hacienda(): void
    {
        $response = $this->withHeader('origin', config('app.url'))->postJson('api/login', [
           'usuario' => 'admin',
           'password' => 'admin',
        ]);

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
        $json->has('login', fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson=>
            $json->where('rol', 'admin')->whereAllType([
            'id' => 'integer',
            'usuario' => 'string',
            'token' => 'string',
            'configuracion.peso_servicio' => 'integer',
            'configuracion.dias_evento_notificacion' => 'integer',
            'configuracion.dias_diferencia_vacuna' => 'integer',
            'hacienda'=>'array|null',
            'tiene_preguntas_seguridad'=>'boolean'
        ])->where('sesion_hacienda', true)))->assertSessionHas('hacienda_id', $this->hacienda->id)
        ->assertSessionHas('peso_servicio', $this->userAdmin->configuracion->peso_servicio)
        ->assertSessionHas('dias_evento_notificacion', $this->userAdmin->configuracion->dias_evento_notificacion)
        ->assertSessionHas('dias_diferencia_vacuna', $this->userAdmin->configuracion->dias_diferencia_vacuna);
    }


    public function test_logear_usuario_admin_tiene_varias_haciendas(): void
    {
        $this->generarHaciendas();

        $response = $this->withHeader('origin', config('app.url'))->postJson('api/login', [
            'usuario' => 'admin',
            'password' => 'admin',
        ]);

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
        $json->has('login', fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson=>
            $json->where('rol', 'admin')->whereAllType([
            'id' => 'integer',
            'usuario' => 'string',
            'token' => 'string',
            'configuracion.peso_servicio' => 'integer',
            'configuracion.dias_evento_notificacion' => 'integer',
            'configuracion.dias_diferencia_vacuna' => 'integer',
            'hacienda'=>'array|null',
            'tiene_preguntas_seguridad'=>'boolean'
        ])->where('sesion_hacienda', false)))->assertSessionMissing('hacienda_id')
        ->assertSessionHas('peso_servicio', $this->userAdmin->configuracion->peso_servicio)
        ->assertSessionHas('dias_evento_notificacion', $this->userAdmin->configuracion->dias_evento_notificacion)
        ->assertSessionHas('dias_diferencia_vacuna', $this->userAdmin->configuracion->dias_diferencia_vacuna);
    }

    public function test_logear_usuario_admin_tiene_varias_haciendas_y_crea_sesion_hacienda(): void
    {
        $haciendas=$this->generarHaciendas();
        $randomHacienda=random_int(0,count($haciendas)-1);
        $haciendaId=$haciendas[$randomHacienda]->id;

        //login
        $this->withHeader('origin', config('app.url'))->postJson('api/login', [
            'usuario' => 'admin',
            'password' => 'admin',
        ]);

        //crear sesion hacienda
        $this->getJson(route('crear_sesion_hacienda', ['hacienda' => $haciendaId]));

        //obtener sesion hacienda
        $response = $this->getJson(route('verificar_sesion_hacienda'));

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
        $json->has('hacienda', fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->where('id', $haciendaId)
            ->etc()
            )
        )->assertSessionHas('peso_servicio', $this->userAdmin->configuracion->peso_servicio)
        ->assertSessionHas('dias_evento_notificacion', $this->userAdmin->configuracion->dias_evento_notificacion)
        ->assertSessionHas('dias_diferencia_vacuna', $this->userAdmin->configuracion->dias_diferencia_vacuna)
        ->assertSessionHas('hacienda_id', $haciendaId);
    }


    public function test_logear_usuario_veterinario_trabjando_en_varias_haciendas(): void
    {
        $this->userVeterinarioEnVariasHaciendas();

        $response = $this->withHeader('origin', config('app.url'))->postJson('api/login', [
            'usuario' => 'veterinario2',
            'password' => 'veterinario2',
        ]);

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
        $json->has('login', fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson=>
            $json->where('rol', 'veterinario')->whereAllType([
            'id' => 'integer',
            'usuario' => 'string',
            'token' => 'string',
            'configuracion.peso_servicio' => 'integer',
            'configuracion.dias_evento_notificacion' => 'integer',
            'configuracion.dias_diferencia_vacuna' => 'integer',
            'hacienda'=>'array|null',
            'tiene_preguntas_seguridad'=>'boolean'
        ])->where('sesion_hacienda', false))
        )
        ->assertSessionMissing('hacienda_id')
        ->assertSessionHas('peso_servicio', $this->userAdmin->configuracion->peso_servicio)
        ->assertSessionHas('dias_evento_notificacion', $this->userAdmin->configuracion->dias_evento_notificacion)
        ->assertSessionHas('dias_diferencia_vacuna', $this->userAdmin->configuracion->dias_diferencia_vacuna);
    }

    public function test_logear_usuario_veterinario_con_admin_tiene_una_hacienda(): void
    {
        $response = $this->withHeader('origin', config('app.url'))->postJson('api/login', [
            'usuario' => 'veterinario',
            'password' => 'veterinario',
        ]);

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
        $json->has('login', fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson=>
        $json->where('rol', 'veterinario')->whereAllType([
        'id' => 'integer',
        'usuario' => 'string',
        'token' => 'string',
        'configuracion.peso_servicio' => 'integer',
        'configuracion.dias_evento_notificacion' => 'integer',
        'configuracion.dias_diferencia_vacuna' => 'integer',
        'hacienda'=>'array|null',
        'tiene_preguntas_seguridad'=>'boolean'
    ])->where('sesion_hacienda', true)))->assertSessionHas('hacienda_id', $this->hacienda->id)
        ->assertSessionHas('peso_servicio', $this->userAdmin->configuracion->peso_servicio)
        ->assertSessionHas('dias_evento_notificacion', $this->userAdmin->configuracion->dias_evento_notificacion)
        ->assertSessionHas('dias_diferencia_vacuna', $this->userAdmin->configuracion->dias_diferencia_vacuna);
    }
}
