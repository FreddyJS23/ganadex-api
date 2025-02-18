<?php

namespace Tests\Feature;

use App\Models\CausasFallecimiento;
use App\Models\Comprador;
use App\Models\Finca;
use App\Models\Ganado;
use App\Models\Toro;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class ToroTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    private array $toro = [
        'nombre' => 'test',
        'numero' => 392,
        'origen' => 'local',
        'sexo' => 'M',
        'tipo_id' => 4,
        'fecha_nacimiento' => '2015-02-17',
        'estado_id' => [1],
        'vacunas' => [
            [
                'fecha' => '2015-02-17',
                'vacuna_id' => 1,
                'prox_dosis' => '2015-02-17',
            ],
            [
                'fecha' => '2015-02-17',
                'vacuna_id' => 2,
                'prox_dosis' => '2015-02-17',
            ],
        ],

    ];

    private array $toroActualizado = [
        'nombre' => 'actualizado',
        'origen' => 'externo',
        'fecha_nacimiento' => '2010-02-17',
        'peso_nacimiento' => 50,
        'peso_destete' => 70,
        'peso_2year' => 90,
        'peso_actual' => 100,
    ];

    private int $cantidad_toro = 10;

    private $user;
    private $finca;
    private $toro_fallecido;
    private $toro_vendido;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->hasConfiguracion()->create();

            $this->user->assignRole('admin');

            $this->finca
            = Finca::factory()
            ->for($this->user)
            ->create();

            $comprador = Comprador::factory()->for($this->finca)->create()->id;
            $causaFallecimiento = CausasFallecimiento::factory()->create();
            $this->toro_fallecido = array_merge($this->toro, ['estado_id' => [2,3,4],'fecha_fallecimiento' => '2020-10-02','descripcion'=>'test','causas_fallecimiento_id'=>$causaFallecimiento->id]);
            $this->toro_vendido = array_merge($this->toro, ['estado_id' => [5,6,7],'fecha_venta' => '2020-10-02','precio' => 100,'comprador_id' => $comprador]);
            $this->toro = array_merge($this->toro, ['estado_id' => [1]]);
    }

    private function generarToros(): Collection
    {
        return Toro::factory()
            ->count(10)
            ->for($this->finca)
            ->for(Ganado::factory()->hasVacunaciones(3, ['finca_id' => $this->finca->id])->create( ['finca_id' => $this->finca->id, 'sexo' => 'M', 'tipo_id' => 4])  )
            ->create();
    }

    private function cambiarRol(User $user): void
    {
        $user->syncRoles('veterinario');
    }


    public static function ErrorInputProvider(): array
    {
        return [
            'caso de que exista el nombre o numero' => [
                [
                    'nombre' => 'test',
                    'numero' => 300,
                    'origen' => 'local',
                    'sexo' => 'M',
                    'tipo_id' => '4',
                    'fecha_nacimiento' => '2015-03-02',
                    'estado_id' => [1],

                ], ['nombre', 'numero']
            ],
            'caso de insertar datos erróneos' => [
                [
                    'nombre' => 'te',
                    'numero' => 'hj',
                    'origen' => 'ce',
                    'estado_id' => [1],
                    'fecha_nacimiento' => '2015-13-02',
                ], [
                    'nombre', 'numero', 'origen', 'fecha_nacimiento',
                ]
            ],
            'caso de no insertar datos requeridos' => [
                ['origen' => 'local', 'estado_id' => [1],
            ], ['nombre', 'numero',]
            ],
        ];
    }



    /**
     * A basic feature test example.
     */

    public function test_obtener_toros(): void
    {
        $this->generarToros();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson('api/toro');

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'toros',
                    $this->cantidad_toro,
                    fn (AssertableJson $json) => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string',
                            'numero' => 'integer',
                            'origen' => 'string',
                            'fecha_nacimiento' => 'string',
                            'ganado_id' => 'integer',
                            'estados' => 'array',
                            'pesos' => 'array|null',
                            'efectividad' => 'double|null',
                            'padre_en_partos' => 'integer',
                            'servicios' => 'integer|null',

                        ])
                    ->where('sexo', 'M')
                    ->where('tipo', 'adulto')
                )
            );
    }


    public function test_creacion_toro(): void
    {

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson('api/toro', $this->toro);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'toro',
                    fn (AssertableJson $json) => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string',
                            'numero' => 'integer',
                            'origen' => 'string',
                            'fecha_nacimiento' => 'string',
                            'ganado_id' => 'integer',
                            'estados' => 'array',
                            'pesos' => 'array|null',
                            'efectividad' => 'double|null',
                            'padre_en_partos' => 'integer|null',
                            'servicios' => 'integer|null',
                        ])
                        ->where('sexo', 'M')
                        ->where('tipo', 'adulto')
                )
            );
    }

    public function test_creacion_toro_fallecida(): void
    {
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson('api/toro', $this->toro_fallecido);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has(
                    'toro',
                    fn (AssertableJson $json) =>
                    $json
                        ->where('estados.0.estado', 'fallecido')
                        ->etc()
                )
            );
    }

    public function test_creacion_toro_vendido(): void
    {
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson('api/toro', $this->toro_vendido);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has(
                    'toro',
                    fn (AssertableJson $json) =>
                    $json
                        ->where('estados.0.estado', 'vendido')
                        ->etc()
                )
            );
    }


    public function test_obtener_toro(): void
    {
        $toros = $this->generarToros();
        $idRandom = random_int(0, $this->cantidad_toro - 1);
        $idToro = $toros[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/toro/%s', $idToro));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'toro',
                    fn (AssertableJson $json) => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string',
                            'numero' => 'integer',
                            'origen' => 'string',
                            'fecha_nacimiento' => 'string',
                            'ganado_id' => 'integer',
                            'estados' => 'array',
                            'pesos' => 'array|null',
                            'efectividad' => 'double|null',
                            'padre_en_partos' => 'integer',
                            'servicios' => 'integer|null',
                        ])
                        ->where('sexo', 'M')
                        ->where('tipo', 'adulto')
                )->has(
                    'vacunaciones',
                    fn(AssertableJson $json) =>
                    $json->has('vacunas.0', fn(AssertableJson $json)=>
                        $json->whereAllType([
                            'vacuna' => 'string',
                            'cantidad' => 'integer',
                            'ultima_dosis' => 'string',
                            'prox_dosis' => 'string',
                        ])
                        ->where('cantidad', fn(int $cantidad)=>$cantidad <= 3))
                    ->has('historial.0', fn(AssertableJson $json)=>
                        $json->whereAllType([
                            'id' => 'integer',
                            'vacuna' => 'string',
                            'fecha' => 'string',
                            'prox_dosis' => 'string',
                        ]))
                )
            );
    }

    public function test_actualizar_toro(): void
    {

        $toroActual = Toro::factory()
        ->for($this->finca)
        ->for(Ganado::factory()->hasPeso()->create(['finca_id' => $this->finca->id,
        'sexo' => 'M',
        'tipo_id' => 4,
        'nombre' => 'test',
        'numero' => 392,
        'origen' => 'local',
        'fecha_nacimiento' => '2015-02-17']))
        ->create();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(sprintf('api/toro/%s', $toroActual->id), $this->toroActualizado);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->where('toro.nombre', $this->toroActualizado['nombre'])
                ->where('toro.numero', $toroActual['ganado']['numero'])
                ->where('toro.origen', $this->toroActualizado['origen'])
                ->where('toro.sexo', $toroActual['ganado']['sexo'])
                ->where('toro.fecha_nacimiento', $this->toroActualizado['fecha_nacimiento'])
                ->where('toro.pesos.peso_nacimiento', $this->toroActualizado['peso_nacimiento'] . 'KG')
                ->where('toro.pesos.peso_destete', $this->toroActualizado['peso_destete'] . 'KG')
                ->where('toro.pesos.peso_2year', $this->toroActualizado['peso_2year'] . 'KG')
                ->where('toro.pesos.peso_actual', $this->toroActualizado['peso_actual'] . 'KG')
                ->etc()
        );
    }

    public function test_actualizar_toro_con_otro_existente_repitiendo_campos_unicos(): void
    {
        Toro::factory()
            ->for($this->finca)
            ->for(Ganado::factory()->for($this->finca)->create(['nombre' => 'test', 'numero' => 392]))
            ->create();

        $toros = $this->generarToros();
        $idRandom = random_int(0, $this->cantidad_toro - 1);
        $idToroEditar = $toros[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(sprintf('api/toro/%s', $idToroEditar), $this->toro);

        $response->assertStatus(422)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['errors.nombre', 'errors.numero'])
        ->etc());
    }

    public function test_actualizar_toro_sin_modificar_campos_unicos(): void
    {
        $toro = Toro::factory()
            ->for($this->finca)
            ->for(Ganado::factory()->hasPeso()->for($this->finca)->create(['nombre' => 'test', 'numero' => 392]))
            ->create();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(sprintf('api/toro/%s', $toro->id), $this->toro);

        $response->assertStatus(200)->assertJson(['toro' => true]);
    }


    public function test_eliminar_toro(): void
    {
        $toros = $this->generarToros();
        $idRandom = random_int(0, $this->cantidad_toro - 1);
        $idToDelete = $toros[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->deleteJson(sprintf('api/toro/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['toroID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_toro($toro, $errores): void
    {
        Toro::factory()
            ->for($this->finca)
            ->for(Ganado::factory()->for($this->finca)->create(['nombre' => 'test', 'numero' => 300]))
            ->create();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson('api/toro', $toro);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_autorizacion_maniupular__toro_otro_finca(): void
    {
        $otroFinca = Finca::factory()
        ->for($this->user)
        ->create(['nombre' => 'otro_finca']);

        $toroOtroFinca = Toro::factory()
            ->for($otroFinca)
            ->for(Ganado::factory()->for($otroFinca))
            ->create();

        $idToroOtroFinca = $toroOtroFinca->id;

        $this->generarToros();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(sprintf('api/toro/%s', $idToroOtroFinca), $this->toro);

        $response->assertStatus(403);
    }

    public function test_veterinario_no_autorizado_a_crear_toro(): void
    {
        $this->cambiarRol($this->user);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(route('toro.store'), $this->toro);

        $response->assertStatus(403);
    }

    public function test_veterinario_no_autorizado_a_actualizar_toro(): void
    {
        $this->cambiarRol($this->user);

        $toro = $this->generarToros();
        $idRandom = random_int(0, $this->cantidad_toro - 1);
        $idToroEditar = $toro[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(route('toro.update', ['toro' => $idToroEditar]), $this->toro);

        $response->assertStatus(403);
    }


    public function test_veterinario_no_autorizado_a_eliminar_toro(): void
    {
        $this->cambiarRol($this->user);

        $toro = $this->generarToros();
        $idRandom = random_int(0, $this->cantidad_toro - 1);
        $idToroEliminar = $toro[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->deleteJson(route('toro.destroy', ['toro' => $idToroEliminar]));

        $response->assertStatus(403);
    }
}
