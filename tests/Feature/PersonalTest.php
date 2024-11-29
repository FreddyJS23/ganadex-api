<?php

namespace Tests\Feature;

use App\Models\Cargo;
use App\Models\Finca;
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
        'telefono' => '0424-1234567',
        'cargo_id' => 1,
        /*  'sueldo' => 60, */
    ];

    private int $cantidad_personal = 10;

    private $user;
    private $finca;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();

            $this->user->assignRole('admin');

            $this->finca
            = Finca::factory()
            ->hasAttached($this->user)
            ->create();
    }

    private function generarPersonal(): Collection
    {
        return Personal::factory()
            ->count($this->cantidad_personal)
            ->for($this->finca)
            ->create();
    }

    private function cambiarRol(User $user): void
    {
        $user->syncRoles('veterinario');
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
                    'cargo_id' => 'obrero',
                    'telefono' => '0424-4232123',
                    /* 'sueldo' => 60, */

                ], ['ci']
            ],
            'caso de insertar datos erróneos' => [
                [
                    'ci' => 3328472738,
                    'nombre' => 'an',
                    'apellido' => 'ez',
                    'fecha_nacimiento' => '20-12-1',
                    'cargo_id' => 'jj',
                    'telefono' => '04241234567',
                    /*    'sueldo' => 'ik', */
                ], ['ci', 'nombre', 'apellido', 'fecha_nacimiento', 'cargo_id','telefono', /* 'sueldo' */]
            ],
            'caso de no insertar datos requeridos' => [
                [],
                ['ci', 'nombre', 'apellido', 'fecha_nacimiento', 'cargo_id','telefono',/*  'sueldo' */]
            ],
            'caso de insertar un cargo inexistente' => [
                [
                    'cargo_id'=>999
                ],
                ['cargo_id']
            ],
        ];
    }



    /**
     * A basic feature test example.
     */

    public function test_obtener_todo_personal(): void
    {
        $this->generarPersonal();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->getJson('api/personal');
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
                        'telefono' => 'string',
                        'cargo' => 'string',
                    ])
                )
            );
    }


    public function test_creacion_personal(): void
    {

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->postJson('api/personal', $this->personal);

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
                        'telefono' => 'string',
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

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->getJson(sprintf('api/personal/%s', $idPersonal));

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
                        'telefono' => 'string',
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

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->putJson(sprintf('api/personal/%s', $idPersonalEditar), $this->personal);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'personal',
                    fn (AssertableJson $json) => $json
                    ->where('ci',$this->personal['ci'])
                    ->where('nombre',$this->personal['nombre'])
                    ->where('apellido',$this->personal['apellido'])
                    ->where('fecha_nacimiento',$this->personal['fecha_nacimiento'])
                    ->where('telefono',$this->personal['telefono'])
                    ->where('cargo',Cargo::find($this->personal['cargo_id'])->cargo)
                    ->etc()
                )
            );
    }

    public function test_actualizar_personal_con_otro_existente_repitiendo_campos_unicos(): void
    {
        $personalExistente = Personal::factory()->for($this->finca)->create(['ci' => 28472738]);

        $personal = $this->generarPersonal();
        $idRandom = rand(0, $this->cantidad_personal - 1);
        $idPersonalEditar = $personal[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->putJson(sprintf('api/personal/%s', $idPersonalEditar), $this->personal);

        $response->assertStatus(422)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['errors.ci'])
            ->etc());
    }

    public function test_actualizar_personal_conservando_campos_unicos(): void
    {
        $personalExistente = Personal::factory()->for($this->finca)->create(['ci' => 28472738]);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->putJson(sprintf('api/personal/%s', $personalExistente->id), $this->personal);

        $response->assertStatus(200);
    }


    public function test_eliminar_personal(): void
    {
        $personals = $this->generarPersonal();
        $idRandom = rand(0, $this->cantidad_personal - 1);
        $idToDelete = $personals[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->deleteJson(sprintf('api/personal/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['personalID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_personal($personal, $errores): void
    {
        personal::factory()->for($this->finca)->create(['ci' => 28472738]);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->postJson('api/personal', $personal);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_autorizacion_maniupular__personal_otro_finca(): void
    {
        $otroFinca = Finca::factory()
        ->hasAttached($this->user)
        ->create(['nombre' => 'otro_finca']);

        $personalOtroFinca = personal::factory()->for($otroFinca)->create();

        $idPersonalOtroFinca = $personalOtroFinca->id;

        $this->generarPersonal();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->putJson(sprintf('api/personal/%s', $idPersonalOtroFinca), $this->personal);

        $response->assertStatus(403);
    }

    public function test_veterinario_no_autorizado_a_crear_personal(): void
    {
        $this->cambiarRol($this->user);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->postJson(route('personal.store'), $this->personal);

        $response->assertStatus(403);
    }

    public function test_veterinario_no_autorizado_a_actualizar_personal(): void
    {
        $this->cambiarRol($this->user);

        $personal = $this->generarPersonal();
        $idRandom = rand(0, $this->cantidad_personal - 1);
        $idPersonalEditar = $personal[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->putJson(route('personal.update',['personal'=>$idPersonalEditar]), $this->personal);

        $response->assertStatus(403);
    }


    public function test_veterinario_no_autorizado_a_eliminar_personal(): void
    {
        $this->cambiarRol($this->user);

        $personal = $this->generarPersonal();
        $idRandom = rand(0, $this->cantidad_personal - 1);
        $idPersonalEditar = $personal[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->deleteJson(route('personal.destroy',['personal'=>$idPersonalEditar]));

        $response->assertStatus(403);
    }
}
