<?php

namespace Tests\Feature;

use App\Models\Hacienda;
use App\Models\RespuestasSeguridad;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class RespuestasSeguridadTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $hacienda;
    private $cantidad_repuestasSeguridad = 10;
    private array $respuestasSeguridad = [
        'pregunta_seguridad_id' => 1,
        'respuesta' => 'mascota',
    ];

    private array $nuevaRespuestaSeguridad = [
        'pregunta_seguridad_id' => 2,
        'respuesta' => 'de maravilla',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->hasConfiguracion()->create();

        $this->hacienda
            = Hacienda::factory()
            ->for($this->user)
            ->create();

        $this->user->assignRole('admin');
    }


    private function generarRespuestasSeguridad(int $count = 10): Collection
    {
        return RespuestasSeguridad::factory()
            ->count($count)
            ->for($this->user)
            ->create();
    }

    private function cambiarRol(User $user): void
    {
        $user->syncRoles('veterinario');
    }


    public static function ErrorInputProvider(): array
    {
        return [

            'caso de insertar datos erróneos' => [
                [
                    'pregunta_seguridad_id' => 99090,
                    'respuesta' => 2323,
                ],
                ['pregunta_seguridad_id', 'respuesta']
            ],
            'caso de no insertar datos minimos' => [
                [
                    'pregunta_seguridad_id' => 1,
                    'respuesta' => 'ddd',
                ],
                ['respuesta']
            ],
            'caso de no insertar datos requeridos' => [
                [],
                ['pregunta_seguridad_id', 'respuesta']
            ],
            'caso de insertar peguntas no existentes' => [
                [
                    'pregunta_seguridad_id' => 768,
                    'respuesta' => 'addds',
                ],
                ['pregunta_seguridad_id']
            ],

        ];
    }



    public function test_obtener_preguntas_seguridad_del_usuario_administrador(): void
    {
        $this->generarRespuestasSeguridad();

        $response = $this->actingAs($this->user)->getJson(route('respuestas_seguridad.index'));

        $response->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'respuestas_seguridad',
                    $this->cantidad_repuestasSeguridad,
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'pregunta' => 'string',
                        'pregunta_seguridad_id' => 'integer',
                        'respuesta' => 'string',
                        'updated_at' => 'string',
                    ])
                )
            );
    }


     public function test_creacion_preguntas_seguridad_del_usuario_administrador(): void
    {

        $response = $this->actingAs($this->user)->postJson(route('respuestas_seguridad.store'), $this->respuestasSeguridad);

        $response->assertStatus(201)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                    ->where('message', 'preguntas de seguridad creadas')
            );
    }

     public function test_creacion_preguntas_seguridad_y_el_usuario_todavia_no_tiene_preguntas_seguridad_minimas(): void
    {
        /* se hace manualmente y no con el generador para simular el comportamiento real */
        /* el minimo de preguntas de seguridad es 3 */
         $this->actingAs($this->user)->postJson(route('respuestas_seguridad.store'), $this->respuestasSeguridad);
        $this->actingAs($this->user)->postJson(route('respuestas_seguridad.store'), $this->respuestasSeguridad);

        $response = $this->actingAs($this->user)->getJson(route('usuario.show', ['user' => $this->user->id]));
        $response->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'user',
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->where('tiene_preguntas_seguridad', false)
                        ->etc()
                )
            );
    }

    public function test_creacion_preguntas_seguridad_y_el_usuario_todavia_ya_tiene_preguntas_seguridad_minimas(): void
    {
        //generar dos preguntas
        $this->generarRespuestasSeguridad(2);
        //crear un tercero para activar el minimo de preguntas de seguridad
        /* el minimo de preguntas de seguridad es 3 */
        $this->actingAs($this->user)->postJson(route('respuestas_seguridad.store'), $this->respuestasSeguridad);

        $response = $this->actingAs($this->user)->getJson(route('usuario.show', ['user' => $this->user->id]));
        $response->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'user',
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->where('tiene_preguntas_seguridad', true)
                        ->etc()
                )
            );
    }


    public function test_actualizar_preguntas_seguridad_del_usuario_administrador(): void
    {
        $repuestasSeguridad = $this->generarRespuestasSeguridad();
        $idRandom = random_int(0, $repuestasSeguridad->count() - 1);
        $idRespuestaSeguridadEditar = $repuestasSeguridad[$idRandom]->id;

        $response = $this->actingAs($this->user)->putJson(route('respuestas_seguridad.update', ['respuesta_seguridad' => $idRespuestaSeguridadEditar]), $this->nuevaRespuestaSeguridad);

        $response->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                    ->has('respuesta_seguridad',fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->where('pregunta_seguridad_id',$this->nuevaRespuestaSeguridad['pregunta_seguridad_id'])
                    ->where('respuesta',$this->nuevaRespuestaSeguridad['respuesta'])
                    ->etc())
            );
    }


    public function test_obtener_preguntas_seguridad_del_usuario_veterinario(): void
    {
        $this->generarRespuestasSeguridad();

        $this->cambiarRol($this->user);
        $response = $this->actingAs($this->user)->getJson(route('respuestas_seguridad.index'));

        $response->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'respuestas_seguridad',
                    $this->cantidad_repuestasSeguridad,
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'pregunta' => 'string',
                        'pregunta_seguridad_id' => 'integer',
                        'respuesta' => 'string',
                        'updated_at' => 'string',
                    ])
                )
            );
    }


    public function test_creacion_preguntas_seguridad_del_usuario_veterinario(): void
    {

        $this->cambiarRol($this->user);
        $response = $this->actingAs($this->user)->postJson(route('respuestas_seguridad.store'), $this->respuestasSeguridad);

        $response->assertStatus(201)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                    ->where('message', 'preguntas de seguridad creadas')
            );
    }

    public function test_actualizar_preguntas_seguridad_del_usuario_veterinario(): void
    {
        $repuestasSeguridad = $this->generarRespuestasSeguridad();
        $idRandom = random_int(0, $repuestasSeguridad->count() - 1);
        $idRespuestaSeguridadEditar = $repuestasSeguridad[$idRandom]->id;

        $this->cambiarRol($this->user);
        $response = $this->actingAs($this->user)->putJson(route('respuestas_seguridad.update', ['respuesta_seguridad' => $idRespuestaSeguridadEditar]), $this->nuevaRespuestaSeguridad);

        $response->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                    ->has('respuesta_seguridad',fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->where('pregunta_seguridad_id',$this->nuevaRespuestaSeguridad['pregunta_seguridad_id'])
                    ->where('respuesta',$this->nuevaRespuestaSeguridad['respuesta'])
                    ->etc())
            );
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_respuestas_seguridad(array $respuestasSeguridad, array $errores): void
    {

        $this->cambiarRol($this->user);
        $response = $this->actingAs($this->user)->postJson(route('respuestas_seguridad.store'), $respuestasSeguridad);

        $response->assertStatus(422)->assertInvalid($errores);
    }


     public function test_eliminar_respuesta_seguridad(): void
    {
        $repuestasSeguridad = $this->generarRespuestasSeguridad();
        $idRandom = random_int(0, $repuestasSeguridad->count() - 1);
        $idToDelete = $repuestasSeguridad[$idRandom]->id;


        $response = $this->actingAs($this->user)->deleteJson(route('respuestas_seguridad.destroy', ['respuesta_seguridad' => $idToDelete]));

        $response->assertStatus(200)->assertJson(['respuestaSeguridadID' => $idToDelete]);
    }


    public function test_eliminar_respuesta_seguridad_y_el_usuario_deja_de_tener_preguntas_seguridad_minimas(): void
    {
        $repuestasSeguridad = $this->generarRespuestasSeguridad(2);
        $idRandom = random_int(0, $repuestasSeguridad->count() - 1);
        $idToDelete = $repuestasSeguridad[$idRandom]->id;

        /* creacion de una tercera respuesta de seguridad para que el usuario ya tenga el minimo de preguntas de seguridad */
        $response = $this->actingAs($this->user)->postJson(route('respuestas_seguridad.store'), $this->respuestasSeguridad);

        /*eliminacion de la respuesta de seguridad*/
        $this->actingAs($this->user)->deleteJson(route('respuestas_seguridad.destroy', ['respuesta_seguridad' => $idToDelete]));

        $response = $this->actingAs($this->user)->getJson(route('usuario.show', ['user' => $this->user->id]));
        $response->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'user',
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->where('tiene_preguntas_seguridad', false)
                        ->etc()
                )
            );
    }


    public function test_eliminar_respuesta_seguridad_y_el_usuario_sigue_teniendo_preguntas_seguridad_minimas(): void
    {
        $repuestasSeguridad = $this->generarRespuestasSeguridad(3);
        $idRandom = random_int(0, $repuestasSeguridad->count() - 1);
        $idToDelete = $repuestasSeguridad[$idRandom]->id;

        /* creacion de una cuarta respuesta de seguridad para que el usuario ya tenga el minimo de preguntas de seguridad */
        $response = $this->actingAs($this->user)->postJson(route('respuestas_seguridad.store'), $this->respuestasSeguridad);

        /*eliminacion de la respuesta de seguridad*/
        $this->actingAs($this->user)->deleteJson(route('respuestas_seguridad.destroy', ['respuesta_seguridad' => $idToDelete]));

        $response = $this->actingAs($this->user)->getJson(route('usuario.show', ['user' => $this->user->id]));
        $response->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'user',
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->where('tiene_preguntas_seguridad', true)
                        ->etc()
                )
            );
    }
}
