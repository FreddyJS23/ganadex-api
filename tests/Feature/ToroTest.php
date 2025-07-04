<?php

namespace Tests\Feature;

use App\Models\CausasFallecimiento;
use App\Models\Comprador;
use App\Models\Hacienda;
use App\Models\Ganado;
use App\Models\Personal;
use App\Models\Servicio;
use App\Models\Toro;
use App\Models\User;
use Carbon\Carbon;
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
        'origen_id' => 1,
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
        'origen_id' => 2,
        'fecha_nacimiento' => '2010-02-17',
        'fecha_ingreso' => '2020-02-17',
        'peso_nacimiento' => 50,
        'peso_destete' => 70,
        'peso_2year' => 90,
        'peso_actual' => 100,
    ];

    private int $cantidad_toro = 10;

    private $user;
    private $hacienda;
    private $toro_fallecido;
    private $toro_vendido;
    private $veterinario;

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

            $this->veterinario
        = Personal::factory()
            ->for($this->user)->hasAttached($this->hacienda)
            ->create(['cargo_id' => 2]);

            $comprador = Comprador::factory()->for($this->hacienda)->create()->id;
            $causaFallecimiento = CausasFallecimiento::factory()->create();
            $this->toro_fallecido = array_merge($this->toro, ['estado_id' => [2,3,4],'fecha_fallecimiento' => '2020-10-02','descripcion'=>'test','causas_fallecimiento_id'=>$causaFallecimiento->id]);
            $this->toro_vendido = array_merge($this->toro, ['estado_id' => [5,6,7],'fecha_venta' => '2020-10-02','precio' => 100,'comprador_id' => $comprador]);
            $this->toro = array_merge($this->toro, ['estado_id' => [1]]);
    }

    private function generarToros(): Collection
    {
        $ganadoFactory=Ganado::factory(['hacienda_id' => $this->hacienda->id, 'sexo' => 'M', 'tipo_id' => 4])
        ->hasVacunaciones(3, ['hacienda_id' => $this->hacienda->id]);

        //usar state para asegurarse de que cada toro tiene una ganado distinta
        return Toro::factory()
            ->count(10)
            ->for($this->hacienda)
            ->state(['ganado_id'=>$ganadoFactory])
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
                    'origen_id' => 1,
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
                    'origen_id' => 86,
                    'estado_id' => [1],
                    'fecha_nacimiento' => '2015-13-02',
                ], [
                    'nombre', 'numero', 'origen_id', 'fecha_nacimiento',
                ]
            ],
            'caso de no insertar datos requeridos' => [
                [ 'estado_id' => [1],
            ], ['nombre', 'numero','origen_id']
            ],
            'caso de insertar que es origen externo y no se coloca fecha de ingreso' => [
                [
                    'origen_id' => 2,
                    'estado_id' => [1]
                ], ['fecha_ingreso']
            ],
        ];
    }



    /**
     * A basic feature test example.
     */

    public function test_obtener_toros(): void
    {
        $this->generarToros();

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson('api/toro');

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'toros',
                    $this->cantidad_toro,
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string',
                            'numero' => 'integer|null',
                            'origen' => 'string',
                            'fecha_nacimiento' => 'string',
                            'fecha_ingreso' => 'string|null',
                            'ganado_id' => 'integer',
                            'estados' => 'array',
                            'pesos' => 'array|null',
                            'efectividad' => 'double|null',
                            'padre_en_partos' => 'integer',
                            'servicios' => 'integer|null',
                            'fallecimiento' => 'array|null',
                            'venta' => 'array|null',


                        ])
                    ->where('sexo', 'M')
                    ->where('tipo', 'Adulto')
                )
            );
    }


    public function test_creacion_toro(): void
    {

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson('api/toro', $this->toro);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'toro',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string',
                            'numero' => 'integer|null',
                            'origen' => 'string',
                            'fecha_nacimiento' => 'string',
                            'fecha_ingreso' => 'string|null',
                            'ganado_id' => 'integer',
                            'estados' => 'array',
                            'pesos' => 'array|null',
                            'efectividad' => 'double|null',
                            'padre_en_partos' => 'integer|null',
                            'servicios' => 'integer|null',
                            'fallecimiento' => 'array|null',
                            'venta' => 'array|null',

                        ])
                        ->where('sexo', 'M')
                        ->where('tipo', 'Adulto')
                )
            );
    }


    public function test_creacion_toro_externo(): void
    {
        //datos que hacen referencia a que el toro es de origen externo
        $this->toro['origen_id'] = 2;
        $this->toro['fecha_ingreso'] = '2020-02-17';

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson('api/toro', $this->toro);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'toro',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string',
                            'numero' => 'integer|null',
                            'origen' => 'string',
                            'fecha_nacimiento' => 'string',
                            'ganado_id' => 'integer',
                            'estados' => 'array',
                            'pesos' => 'array|null',
                            'efectividad' => 'double|null',
                            'padre_en_partos' => 'integer|null',
                            'servicios' => 'integer|null',
                            'sexo' => 'string',
                            'tipo' => 'string',
                            'fallecimiento' => 'array|null',
                            'venta' => 'array|null',

                        ])
                    ->where('origen', 'Externo')
                    ->where('fecha_ingreso', Carbon::parse( $this->toro['fecha_ingreso'])->format('d-m-Y'))
                )
            );
    }

    public function test_creacion_toro_fallecida(): void
    {
        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson('api/toro', $this->toro_fallecido);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                $json->has(
                    'toro',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json
                    ->has('fallecimiento',
                        fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson=>
                        $json->where('fecha',Carbon::parse( $this->toro_fallecido['fecha_fallecimiento'])->format('d-m-Y'))
                        ->where('descripcion', $this->toro_fallecido['descripcion'])
                         ->whereType('causa','string')
                    )
                    ->where('estados.0.estado', 'fallecido')
                        ->etc()
                )

            );
    }

    public function test_creacion_toro_vendido(): void
    {
        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson('api/toro', $this->toro_vendido);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                $json->has(
                    'toro',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
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


        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/toro/%s', $idToro));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'toro',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string',
                            'numero' => 'integer|null',
                            'origen' => 'string',
                            'fecha_nacimiento' => 'string',
                             'fecha_ingreso' => 'string|null',
                            'ganado_id' => 'integer',
                            'estados' => 'array',
                            'pesos' => 'array|null',
                            'efectividad' => 'double|null',
                            'padre_en_partos' => 'integer',
                            'servicios' => 'integer|null',
                            'fallecimiento' => 'array|null',
                            'venta' => 'array|null',

                        ])
                        ->where('sexo', 'M')
                        ->where('tipo', 'Adulto')
                )->has(
                    'vacunaciones',
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->has('vacunas.0', fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson=>
                        $json->whereAllType([
                            'vacuna' => 'string',
                            'cantidad' => 'integer',
                            'ultima_dosis' => 'string',
                            'prox_dosis' => 'string',
                        ])
                        ->where('cantidad', fn(int $cantidad): bool=>$cantidad <= 3))
                    ->has('historial.0', fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson=>
                        $json->whereAllType([
                            'id' => 'integer',
                            'vacuna' => 'string',
                            'fecha' => 'string',
                            'prox_dosis' => 'string',
                        ]))
                )
            );
    }

    public function test_obtener_servicios_toro(): void
    {
        $toros = $this->generarToros();
        $idRandom = random_int(0, $this->cantidad_toro - 1);
        $toro = $toros[$idRandom];

        Servicio::factory()
            ->count(3)
            ->for(Ganado::factory()->for($this->hacienda)->create(['sexo' => 'M']))
            ->for($toro, 'servicioable')
            ->create(['personal_id' => $this->veterinario]);

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('toro.servicios', ['toro' => $toro->id]));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'servicios',
                    3,
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                    ->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'observacion' => 'string',
                        'vaca' => 'array',
                        'veterinario' => 'array',
                    ])
                )
            );
    }

    public function test_actualizar_toro(): void
    {

        $toroActual = Toro::factory()
        ->for($this->hacienda)
        ->for(Ganado::factory()->hasPeso()->create(['hacienda_id' => $this->hacienda->id,
        'sexo' => 'M',
        'tipo_id' => 4,
        'nombre' => 'test',
        'numero' => 392,
        'origen_id' => 1,
        'fecha_nacimiento' => '2015-02-17']))
        ->create();

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(sprintf('api/toro/%s', $toroActual->id), $this->toroActualizado);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json
                ->where('toro.nombre', $this->toroActualizado['nombre'])
                ->where('toro.numero', $toroActual['ganado']['numero'])
                ->where('toro.origen',  'Externo') //origen_id = 2
                ->where('toro.sexo', $toroActual['ganado']['sexo'])
                ->where('toro.fecha_nacimiento', Carbon::parse( $this->toroActualizado['fecha_nacimiento'])->format('d-m-Y'))
                ->where('toro.fecha_ingreso', Carbon::parse( $this->toroActualizado['fecha_ingreso'])->format('d-m-Y'))
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
            ->for($this->hacienda)
            ->for(Ganado::factory()->for($this->hacienda)->create(['nombre' => 'test', 'numero' => 392]))
            ->create();

        $toros = $this->generarToros();
        $idRandom = random_int(0, $this->cantidad_toro - 1);
        $idToroEditar = $toros[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(sprintf('api/toro/%s', $idToroEditar), $this->toro);

        $response->assertStatus(422)->assertJson(fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
        $json->hasAll(['errors.nombre', 'errors.numero'])
        ->etc());
    }

    public function test_actualizar_toro_sin_modificar_campos_unicos(): void
    {
        $toro = Toro::factory()
            ->for($this->hacienda)
            ->for(Ganado::factory()->hasPeso()->for($this->hacienda)->create(['nombre' => 'test', 'numero' => 392]))
            ->create();

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(sprintf('api/toro/%s', $toro->id), $this->toro);

        $response->assertStatus(200)->assertJson(['toro' => true]);
    }


    public function test_eliminar_toro(): void
    {
        $toros = $this->generarToros();
        $idRandom = random_int(0, $this->cantidad_toro - 1);
        $idToDelete = $toros[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->deleteJson(sprintf('api/toro/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['toroID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_toro(array $toro, array $errores): void
    {
        Toro::factory()
            ->for($this->hacienda)
            ->for(Ganado::factory()->for($this->hacienda)->create(['nombre' => 'test', 'numero' => 300]))
            ->create();

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson('api/toro', $toro);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_autorizacion_maniupular__toro_otro_hacienda(): void
    {
        $otroHacienda = Hacienda::factory()
        ->for($this->user)
        ->create(['nombre' => 'otro_hacienda']);

        $toroOtroHacienda = Toro::factory()
            ->for($otroHacienda)
            ->for(Ganado::factory()->for($otroHacienda))
            ->create();

        $idToroOtroHacienda = $toroOtroHacienda->id;

        $this->generarToros();

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(sprintf('api/toro/%s', $idToroOtroHacienda), $this->toro);

        $response->assertStatus(403);
    }

    public function test_veterinario_no_autorizado_a_crear_toro(): void
    {
        $this->cambiarRol($this->user);

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(route('toro.store'), $this->toro);

        $response->assertStatus(403);
    }

    public function test_veterinario_no_autorizado_a_actualizar_toro(): void
    {
        $this->cambiarRol($this->user);

        $toro = $this->generarToros();
        $idRandom = random_int(0, $this->cantidad_toro - 1);
        $idToroEditar = $toro[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(route('toro.update', ['toro' => $idToroEditar]), $this->toro);

        $response->assertStatus(403);
    }


    public function test_veterinario_no_autorizado_a_eliminar_toro(): void
    {
        $this->cambiarRol($this->user);

        $toro = $this->generarToros();
        $idRandom = random_int(0, $this->cantidad_toro - 1);
        $idToroEliminar = $toro[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->deleteJson(route('toro.destroy', ['toro' => $idToroEliminar]));

        $response->assertStatus(403);
    }
}
