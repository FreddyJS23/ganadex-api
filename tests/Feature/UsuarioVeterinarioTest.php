<?php

namespace Tests\Feature;

use App\Models\Hacienda;
use App\Models\Personal;
use App\Models\User;
use App\Models\UsuarioVeterinario;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class UsuarioVeterinarioTest extends TestCase
{
    use RefreshDatabase;

    private array $usuarioVeterinario;
    private $user;
    private $hacienda;
    private $personal;


    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->hasConfiguracion()->create();

        $this->user->assignRole('admin');

        $this->hacienda
            = Hacienda::factory()
            ->for($this->user)
            ->create();

        $this->personal
            = Personal::factory()
            ->for($this->hacienda)
            ->create(['cargo_id' => 2])->id;

        $this->usuarioVeterinario = ['personal_id' => $this->personal];
    }

    public static function ErrorInputProvider(): array
    {
        return [

            'caso de insertar veterinario no registrado' => [
                [
                    'personal_id' => 1943789714
                ],
                ['personal_id']
            ],
            'caso de no insertar datos requeridos' => [
                [],
                ['personal_id']
            ],
        ];
    }


    private function usuariosVeterinarios(): Collection
    {
        return UsuarioVeterinario::factory()
            ->count(10)
            ->for(Personal::factory()->for($this->hacienda)->create(['cargo_id' => 2]), 'veterinario')
            ->create(['admin_id' => $this->user->id]);
    }


    public function test_obtener_usuarios_veterinarios(): void
    {
        $this->usuariosVeterinarios();

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('usuarios_veterinarios.index'));
        $response->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'usuarios_veterinarios',
                    10,
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType([
                        'id' => 'integer',
                        'usuario' => 'string',
                        'nombre' => 'string',
                        'fecha_creacion' => 'string',
                        'telefono' => 'string'
                    ])->where('rol', 'veterinario')
                )
            );
    }


    public function test_creacion_usuario_veterinario(): void
    {
        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(route('usuarios_veterinarios.store'), ['personal_id' => $this->personal]);

        $response->assertStatus(201)->assertJson(
            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                'usuario_veterinario',
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType([
                    'id' => 'integer',
                    'usuario' => 'string',
                    'nombre' => 'string',
                    'fecha_creacion' => 'string',
                    'telefono' => 'string'
                ])
                    ->where('rol', 'veterinario')
            )
        );
    }

    public function test_creacion_usuario_veterinario_con_nombre_veterinario_muy_largo(): void
    {
        $this->personal
        = Personal::factory()
        ->for($this->hacienda)
        ->create(['nombre' => 'sdsdsfdsfjijwiwwjkhkjbjhkgiggyg','cargo_id' => 2])->id;

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(route('usuarios_veterinarios.store'), ['personal_id' => $this->personal]);

        $response->assertStatus(201)->assertJson(
            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                'usuario_veterinario',
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                ->where('usuario', fn(string $usuario): bool=> str_contains($usuario, 'usuario'))
                ->etc()
            )
        );
    }
    public function test_creacion_usuario_veterinario_con_nombre_veterinario_muy_corto(): void
    {
        $this->personal
        = Personal::factory()
        ->for($this->hacienda)
        ->create(['nombre' => 's','cargo_id' => 2])->id;

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(route('usuarios_veterinarios.store'), ['personal_id' => $this->personal]);

        $response->assertStatus(201)->assertJson(
            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                'usuario_veterinario',
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                ->where('usuario', fn(string $usuario): bool=> str_contains($usuario, 'usuario'))
                ->etc()
            )
        );
    }

    public function test_eliminar_usuario_veterinario(): void
    {
        $usuarioVeterinario = $this->usuariosVeterinarios();
        $idRandom = random_int(0, 9);
        $idToDelete = $usuarioVeterinario[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->deleteJson(route('usuarios_veterinarios.destroy', ['usuarios_veterinario' => $idToDelete]));

        $response->assertStatus(200)->assertJson(['usuarioVeterinarioID' => $idToDelete]);
    }


    public function test_sin_autorizacion_eliminar_usuario_otro_administrador(): void
    {
        $otroHacienda = Hacienda::factory()
            ->for($this->user)
            ->create(['nombre' => 'otro_hacienda']);

        $otroAdmin = User::factory()->create(['usuario' => 'test']);

        $otroAdmin->assignRole('admin');

        $usuarioVeterinarioOtroAdmin =  UsuarioVeterinario::factory()
            ->for(Personal::factory()->for($otroHacienda)->create(), 'veterinario')
            ->create(['admin_id' => $otroAdmin->id]);

        $idToDelete = $usuarioVeterinarioOtroAdmin->id;

        $this->usuariosVeterinarios();


        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])
            ->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->deleteJson(route('usuarios_veterinarios.destroy', ['usuarios_veterinario' => $idToDelete]));

        $response->assertStatus(403);
    }


    public function test__usuario_veterinario_sin_autorizacion_crear_usuario_veterinario(): void
    {
        $this->user->syncRoles('veterinario');

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(route('usuarios_veterinarios.store'), ['personal_id' => $this->personal]);

        $response->assertStatus(403);
    }

    public function test__usuario_veterinario_sin_autorizacion_ver_usuarios_veterinario(): void
    {
        $this->user->syncRoles('veterinario');

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('usuarios_veterinarios.index'));

        $response->assertStatus(403);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_usuario_veterinario(array $usuarioVeterinario, array $errores): void
    {

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(route('usuarios_veterinarios.store'), $usuarioVeterinario);

        $response->assertStatus(422)->assertInvalid($errores);
    }
}
