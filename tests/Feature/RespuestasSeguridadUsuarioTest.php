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

class RespuestasSeguridadUsuarioTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $hacienda;
    private $cantidad_repuestasSeguridad = 10;
    private array $respuestasSeguridad = [
        'preguntas' => [1,2,3],
        'respuestas' => ['muy bien','bienn','perrito'],
    ];

    private array $nuevaRespuestaSeguridad = [
        'pregunta_seguridad_id' => 1,
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


    private function generarRespuestasSeguridad(): Collection
    {
        return RespuestasSeguridad::factory()
            ->count($this->cantidad_repuestasSeguridad)
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
                    'preguntas' => ['dd','ddd','dddd'],
                    'respuestas' => [232,4432,544],
                ], ['preguntas.0', 'respuestas.0']
            ],
            'caso de no insertar datos minimos' => [
                [
                    'preguntas' => [1,2],
                    'respuestas' => ['bien','muy bien'],
                ], ['preguntas', 'respuestas']
            ],
            'caso de no insertar datos requeridos' => [
                [], ['preguntas', 'respuestas']
            ],
            'caso de insertar peguntas no existentes' => [
                [
                    'preguntas' => [768],
                    'respuestas' => ['addds','bsssw'],
                ], ['preguntas.0']
            ],

        ];
    }



    public function test_obtener_preguntas_seguridad_del_usuario_administrador(): void
    {
        $this->generarRespuestasSeguridad();

        $response = $this->actingAs($this->user)->getJson(route('respuesta_seguridad.index'));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'preguntas_seguridad',
                    $this->cantidad_repuestasSeguridad,
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'pregunta' => 'string',
                    ])
                )
            );
    }


    public function test_creacion_preguntas_seguridad_del_usuario_administrador(): void
    {

        $response = $this->actingAs($this->user)->postJson(route('respuesta_seguridad.store'), $this->respuestasSeguridad);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                ->where('message', 'preguntas de seguridad creadas')
            );
    }

    public function test_actualizar_preguntas_seguridad_del_usuario_administrador(): void
    {
        $repuestasSeguridad=$this->generarRespuestasSeguridad();
        $idRandom = random_int(0, $repuestasSeguridad->count() - 1);
        $idRespuestaSeguridadEditar = $repuestasSeguridad[$idRandom]->id;

        $response = $this->actingAs($this->user)->putJson(route('respuesta_seguridad.update',['respuesta_seguridad'=>$idRespuestaSeguridadEditar]), $this->nuevaRespuestaSeguridad);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                ->where('message', 'pregunta de seguridad actualizadas')
            );
    }

    public function test_obtener_preguntas_seguridad_del_usuario_veterinario(): void
    {
        $this->generarRespuestasSeguridad();

        $this->cambiarRol($this->user);
        $response = $this->actingAs($this->user)->getJson(route('respuesta_seguridad.index'));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'preguntas_seguridad',
                    $this->cantidad_repuestasSeguridad,
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'pregunta' => 'string',
                    ])
                )
            );
    }


    public function test_creacion_preguntas_seguridad_del_usuario_veterinario(): void
    {

        $this->cambiarRol($this->user);
        $response = $this->actingAs($this->user)->postJson(route('respuesta_seguridad.store'), $this->respuestasSeguridad);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                ->where('message', 'preguntas de seguridad creadas')
            );
    }

    public function test_actualizar_preguntas_seguridad_del_usuario_veterinario(): void
    {
        $repuestasSeguridad=$this->generarRespuestasSeguridad();
        $idRandom = random_int(0, $repuestasSeguridad->count() - 1);
        $idRespuestaSeguridadEditar = $repuestasSeguridad[$idRandom]->id;

        $this->cambiarRol($this->user);
        $response = $this->actingAs($this->user)->putJson(route('respuesta_seguridad.update',['respuesta_seguridad'=>$idRespuestaSeguridadEditar]), $this->nuevaRespuestaSeguridad);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                ->where('message', 'pregunta de seguridad actualizadas')
            );

    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_respuestas_seguridad(array $respuestasSeguridad, array $errores): void
    {

        $this->cambiarRol($this->user);
        $response = $this->actingAs($this->user)->postJson(route('respuesta_seguridad.store'), $respuestasSeguridad);

        $response->assertStatus(422)->assertInvalid($errores);

    }

     public function test_eliminar_respuesta_seguridad(): void
    {
        $repuestasSeguridad=$this->generarRespuestasSeguridad();
        $idRandom = random_int(0, $repuestasSeguridad->count() - 1);
        $idToDelete= $repuestasSeguridad[$idRandom]->id;


        $response = $this->actingAs($this->user)->deleteJson(route('respuesta_seguridad.destroy',['respuesta_seguridad'=>$idToDelete]));

        $response->assertStatus(200)->assertJson(['respuestaSeguridadID' => $idToDelete]);
    }


}
