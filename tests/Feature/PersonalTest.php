<?php

namespace Tests\Feature;

use App\Models\Personal;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class PersonalTest extends TestCase
{
    use RefreshDatabase;

    private array $personal = [
        'ci' => 28472738,
        'nombre' => 'juan',
        'apellido' => 'perez',
        'fecha_nacimiento' => '2000-02-12',
        'cargo' => 'obrero',
        /*  'sueldo' => 60, */
    ];

    private int $cantidad_personal = 10;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();
    }

    private function generarPersonal(): Collection
    {
        return Personal::factory()
            ->count($this->cantidad_personal)
            ->for($this->user)
            ->create();
    }
    public static function ErrorInputProvider(): array
    {
        return [
            'caso de que exista el personal con la misma cedula' => [
                [
                    'ci' => 28472738,
                    'nombre' => 'juan',
                    'apellido' => 'perez',
                    'fecha_nacimiento' => '2000-02-12',
                    'cargo' => 'obrero',
                    /* 'sueldo' => 60, */

                ], ['ci']
            ],
            'caso de insertar datos erróneos' => [
                [
                    'ci' => 3328472738,
                    'nombre' => 'an',
                    'apellido' => 'ez',
                    'fecha_nacimiento' => '20-12-1',
                    'cargo' => 'er',
                    /*    'sueldo' => 'ik', */
                ], ['ci', 'nombre', 'apellido', 'fecha_nacimiento', 'cargo', /* 'sueldo' */]
            ],
            'caso de no insertar datos requeridos' => [
                [],
                ['ci', 'nombre', 'apellido', 'fecha_nacimiento', 'cargo',/*  'sueldo' */]
            ],
        ];
    }



    /**
     * A basic feature test example.
     */

    public function test_obtener_todo_personal(): void
    {
        $this->generarPersonal();

        $response = $this->actingAs($this->user)->getJson('api/personal');
        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'todo_personal',
                    $this->cantidad_personal,
                    fn (AssertableJson $json) => $json->whereAllType([
                        'id' => 'integer',
                        'ci' => 'integer',
                        'nombre' => 'string',
                        'apellido' => 'string',
                        'fecha_nacimiento' => 'string',
                        'cargo' => 'string',
                    ])
                )
            );
    }


    public function test_creacion_personal(): void
    {

        $response = $this->actingAs($this->user)->postJson('api/personal', $this->personal);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'personal',
                    fn (AssertableJson $json) => $json->whereAllType([
                        'id' => 'integer',
                        'ci' => 'integer',
                        'nombre' => 'string',
                        'apellido' => 'string',
                        'fecha_nacimiento' => 'string',
                        'cargo' => 'string',
                    ])
                )
            );
    }


    public function test_obtener_personal(): void
    {
        $personals = $this->generarPersonal();
        $idRandom = rand(0, $this->cantidad_personal - 1);
        $idPersonal = $personals[$idRandom]->id;

        $response = $this->actingAs($this->user)->getJson(sprintf('api/personal/%s', $idPersonal));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'personal',
                    fn (AssertableJson $json) => $json->whereAllType([
                        'id' => 'integer',
                        'ci' => 'integer',
                        'nombre' => 'string',
                        'apellido' => 'string',
                        'fecha_nacimiento' => 'string',
                        'cargo' => 'string',
                    ])
                )
            );
    }

    public function test_actualizar_personal(): void
    {
        $personals = $this->generarPersonal();
        $idRandom = rand(0, $this->cantidad_personal - 1);
        $idPersonalEditar = $personals[$idRandom]->id;

        $response = $this->actingAs($this->user)->putJson(sprintf('api/personal/%s', $idPersonalEditar), $this->personal);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'personal',
                    fn (AssertableJson $json) => $json
                    ->where('ci',$this->personal['ci'])
                    ->where('nombre',$this->personal['nombre'])
                    ->where('apellido',$this->personal['apellido'])
                    ->where('fecha_nacimiento',$this->personal['fecha_nacimiento'])
                    ->where('cargo',$this->personal['cargo'])
                    ->etc()
                )
            );
    }

    public function test_actualizar_personal_con_otro_existente_repitiendo_campos_unicos(): void
    {
        $personalExistente = Personal::factory()->for($this->user)->create(['ci' => 28472738]);

        $personal = $this->generarPersonal();
        $idRandom = rand(0, $this->cantidad_personal - 1);
        $idPersonalEditar = $personal[$idRandom]->id;

        $response = $this->actingAs($this->user)->putJson(sprintf('api/personal/%s', $idPersonalEditar), $this->personal);

        $response->assertStatus(422)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['errors.ci'])
            ->etc());
    }

    public function test_actualizar_personal_conservando_campos_unicos(): void
    {
        $personalExistente = Personal::factory()->for($this->user)->create(['ci' => 28472738]);

        $response = $this->actingAs($this->user)->putJson(sprintf('api/personal/%s', $personalExistente->id), $this->personal);

        $response->assertStatus(200);
    }


    public function test_eliminar_personal(): void
    {
        $personals = $this->generarPersonal();
        $idRandom = rand(0, $this->cantidad_personal - 1);
        $idToDelete = $personals[$idRandom]->id;


        $response = $this->actingAs($this->user)->deleteJson(sprintf('api/personal/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['personalID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_personal($personal, $errores): void
    {
        personal::factory()->for($this->user)->create(['ci' => 28472738]);

        $response = $this->actingAs($this->user)->postJson('api/personal', $personal);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_autorizacion_maniupular__personal_otro_usuario(): void
    {
        $otroUsuario = User::factory()->create();

        $personalOtroUsuario = personal::factory()->for($otroUsuario)->create();

        $idPersonalOtroUsuario = $personalOtroUsuario->id;

        $this->generarPersonal();

        $response = $this->actingAs($this->user)->putJson(sprintf('api/personal/%s', $idPersonalOtroUsuario), $this->personal);

        $response->assertStatus(403);
    }
}
