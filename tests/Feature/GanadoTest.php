<?php

namespace Tests\Feature;

use App\Models\CausasFallecimiento;
use App\Models\Comprador;
use App\Models\Estado;
use App\Models\Finca;
use App\Models\Ganado;
use App\Models\Plan_sanitario;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Support\Str;
use Tests\TestCase;

class GanadoTest extends TestCase
{
    use RefreshDatabase;

    private array $cabeza_ganado = [
        'nombre' => 'test',
        'numero' => 392,
        'origen' => 'local',
        'sexo' => 'H',
        'tipo_id' => 4,
        'fecha_nacimiento' => '2015-02-17',
        'peso_nacimiento' => 30,
        'peso_destete' => 130,
        'peso_2year' => 300,
        'peso_actual' => 60,
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
    private array $cabeza_ganado_actualizada = [
        'nombre' => 'actualizado',
        'origen' => 'externo',
        'fecha_nacimiento' => '2010-02-17',
        'peso_nacimiento' => 50,
        'peso_destete' => 70,
        'peso_2year' => 90,
        'peso_actual' => 100,
    ];

    private int $cantidad_ganado = 10;
    private $cabeza_ganado_fallecida;
    private $cabeza_ganado_vendida;
    private $estado;
    private $user;
    private $finca;

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

            $this->estado = Estado::all();

            $causaFallecimiento = CausasFallecimiento::factory()->create();

            $comprador = Comprador::factory()->for($this->finca)->create()->id;
            $this->cabeza_ganado_fallecida = array_merge($this->cabeza_ganado, ['estado_id' => [2,3,4],'fecha_fallecimiento' => '2020-10-02','descripcion'=>'test','causas_fallecimiento_id'=>$causaFallecimiento->id]);
            $this->cabeza_ganado_vendida = array_merge($this->cabeza_ganado, ['estado_id' => [5,6,7],'fecha_venta' => '2020-10-02','precio' => 100,'comprador_id' => $comprador]);
            $this->cabeza_ganado = array_merge($this->cabeza_ganado, ['estado_id' => [1]]);
    }

    private function generarGanado(): Collection
    {
        return Ganado::factory()
            ->count($this->cantidad_ganado)
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->hasVacunaciones(3, ['finca_id' => $this->finca->id])
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
            'caso de que exista el nombre o numero' => [
                [
                    'nombre' => 'test',
                    'numero' => 300,
                    'origen' => 'local',
                    'tipo_id' => '4',
                    'fecha_nacimiento' => '2015-03-02',
                    'peso_nacimiento' => 30,
                    'peso_destete' => 30,
                    'peso_2year' => 30,
                    'peso_actual' => 30,
                    'estado_id' => [1],
                ], ['nombre', 'numero']
            ],
            'caso de insertar datos erróneos' => [
                [
                    'nombre' => 'te',
                    'numero' => 'hj',
                    'origen' => 'ce',
                    'tipo_id' => '30d',
                    'fecha_nacimiento' => '2015-13-02',
                    'peso_nacimiento' => '30KdG',
                    'peso_destete' => '30Kg',
                    'peso_2year' => 'd30KG',
                    'peso_actual' => '.30KG',
                    'estado_id' => ["f", "fg", 20],
                ], [
                    'nombre', 'numero', 'origen', 'tipo_id', 'fecha_nacimiento',
                    'peso_nacimiento', 'peso_destete', 'peso_2year', 'peso_actual', 'estado_id'
                ]
            ],
            'caso de no insertar datos requeridos' => [
                [
                    'numero' => 300,
                    'origen' => 'local',
                    'fecha_nacimiento' => '2015-03-02',
                    'peso_nacimiento' => 30,
                    'peso_destete' => 30,
                    'peso_2year' => 30,
                    'peso_actual' => 30,
                    'estado_id' => ["f", "fg", 20],
                ], ['nombre', 'tipo_id']
            ],
        ];
    }



    /**
     * A basic feature test example.
     */

    public function test_obtener_cabezas_ganado(): void
    {
        $this->generarGanado();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson('api/ganado');
        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                $json->has('cabezas_ganado', $this->cantidad_ganado)
                    ->has(
                        'cabezas_ganado.0',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                        $json->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string',
                            'numero' => 'integer',
                            'origen' => 'string',
                            'fecha_nacimiento' => 'string',
                            'estados' => 'array',
                            'estados.0.id' => 'integer',
                            'estados.0.estado' => 'string',
                        ])
                            ->where('sexo', fn (string $sexo) => Str::contains($sexo, ['M', 'H']))
                            ->where('tipo', fn (string $tipo) => Str::contains($tipo, ['becerro', 'maute', 'novillo', 'adulto']))
                        ->has(
                            'pesos',
                            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                                ->whereAllType([
                                    'id' => 'integer',
                                    'peso_nacimiento' => 'string',
                                    'peso_destete' => 'string',
                                    'peso_2year' => 'string',
                                    'peso_actual' => 'string',
                                ])
                        )
                        ->has(
                            'eventos',
                            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                                ->whereAllType([
                                    'id' => 'integer',
                                    'prox_revision' => 'string|null',
                                    'prox_parto' => 'string|null',
                                    'prox_secado' => 'string|null',
                                ])
                        )
                    )
            );
    }


    public function test_creacion_cabeza_ganado(): void
    {

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson('api/ganado', $this->cabeza_ganado);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                $json->has(
                    'ganado',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'nombre' => 'string',
                        'numero' => 'integer',
                        'origen' => 'string',
                        'fecha_nacimiento' => 'string',
                        'estados' => 'array',
                        'estados.0.id' => 'integer',
                        'estados.0.estado' => 'string',
                    ])
                        ->where('sexo', fn (string $sexo) => Str::contains($sexo, ['M', 'H']))
                        ->where('tipo', fn (string $tipo) => Str::contains($tipo, ['becerro', 'maute', 'novillo', 'adulto']))
                        ->has(
                            'pesos',
                            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson=>$json
                            ->whereAllType([
                                'id' => 'integer',
                                'peso_nacimiento' => 'string',
                                'peso_destete' => 'string',
                                'peso_2year' => 'string',
                                'peso_actual' => 'string',
                                    ])
                        )
                        ->has(
                            'eventos',
                            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson=>$json
                            ->whereAllType([
                            'id' => 'integer',
                                'prox_revision' => 'string|null',

                            'prox_parto' => 'string|null',
                            'prox_secado' => 'string|null',
                                    ])
                        )
                )
            );
    }

    public function test_creacion_cabeza_ganado_fallecida(): void
    {
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson('api/ganado', $this->cabeza_ganado_fallecida);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                $json->has(
                    'ganado',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json
                        ->where('estados.0.estado', 'fallecido')
                        ->etc()
                )
            );
    }

    public function test_creacion_cabeza_ganado_vendido(): void
    {
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson('api/ganado', $this->cabeza_ganado_vendida);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                $json->has(
                    'ganado',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json
                        ->where('estados.0.estado', 'vendido')
                        ->etc()
                )
            );
    }


    public function test_obtener_cabeza_ganado(): void
    {
        Plan_sanitario::factory()->for($this->finca)->count(2)->create();
        $cabezasGanado = $this->generarGanado();
        $idRandom = random_int(0, $this->cantidad_ganado - 1);
        $idGanado = $cabezasGanado[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/ganado/%s', $idGanado), $this->cabeza_ganado);

        $response->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                $json->has(
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
                )->etc()
            );
    }

    public function test_actualizar_cabeza_ganado(): void
    {
        $ganadoEditar = Ganado::factory()
        ->hasPeso(1)
        ->hasEvento(1)
        ->hasAttached($this->estado)
        ->hasVacunaciones(3, ['finca_id' => $this->finca->id])
        ->for($this->finca)
        ->create([
        'nombre' => 'test',
        'numero' => 392,
        'origen' => 'local',
        'sexo' => 'H',]);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(sprintf('api/ganado/%s', $ganadoEditar->id), $this->cabeza_ganado_actualizada);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json
                ->where('ganado.nombre', $this->cabeza_ganado_actualizada['nombre'])
                ->where('ganado.numero', $ganadoEditar['numero'])
                ->where('ganado.origen', $this->cabeza_ganado_actualizada['origen'])
                ->where('ganado.sexo', $ganadoEditar['sexo'])
                ->where('ganado.fecha_nacimiento', $this->cabeza_ganado_actualizada['fecha_nacimiento'])
                ->where('ganado.pesos.peso_nacimiento', $this->cabeza_ganado_actualizada['peso_nacimiento'] . 'KG')
                ->where('ganado.pesos.peso_destete', $this->cabeza_ganado_actualizada['peso_destete'] . 'KG')
                ->where('ganado.pesos.peso_2year', $this->cabeza_ganado_actualizada['peso_2year'] . 'KG')
                ->where('ganado.pesos.peso_actual', $this->cabeza_ganado_actualizada['peso_actual'] . 'KG')
                ->etc()
        );
    }

    public function test_actualizar_cabeza_ganado_con_otro_existente_repitiendo_campos_unicos(): void
    {
        Ganado::factory()->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->finca)
            ->create(['nombre' => 'test', 'numero' => 392]);

        $cabezasGanado = $this->generarGanado();
        $idRandom = random_int(0, $this->cantidad_ganado - 1);
        $idGanadoEditar = $cabezasGanado[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(sprintf('api/ganado/%s', $idGanadoEditar), $this->cabeza_ganado);

        $response->assertStatus(422)->assertJson(fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
        $json->hasAll(['errors.nombre', 'errors.numero'])
            ->etc());
    }

    public function test_actualizar_cabeza_ganado_sin_modificar_campos_unicos(): void
    {
        $ganado = Ganado::factory()->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->finca)
            ->create(['nombre' => 'test', 'numero' => 392]);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(sprintf('api/ganado/%s', $ganado->id), $this->cabeza_ganado);

        $response->assertStatus(200)->assertJson(['ganado' => true]);
    }

    public function test_eliminar_cabeza_ganado(): void
    {
        $cabezasGanado = $this->generarGanado();
        $idRandom = random_int(0, $this->cantidad_ganado - 1);
        $idToDelete = $cabezasGanado[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->deleteJson(sprintf('api/ganado/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['ganadoID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_cabeza_ganado(array $ganado, array $errores): void
    {
        Ganado::factory()->for($this->finca)->create(['nombre' => 'test', 'numero' => 300]);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson('api/ganado', $ganado);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_autorizacion_maniupular_cabeza_ganado_otra_finca(): void
    {
        $otroFinca = Finca::factory()
        ->for($this->user)
        ->create(['nombre' => 'otro_finca']);

        $ganadoOtroUsuario = Ganado::factory()
           ->hasPeso(1)->hasEvento(1)
           ->hasAttached($this->estado)
           ->for($otroFinca)
           ->create();

        $idGanadoOtroUsuario = $ganadoOtroUsuario->id;

        $this->generarGanado();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(sprintf('api/ganado/%s', $idGanadoOtroUsuario), $this->cabeza_ganado);

        $response->assertStatus(403);
    }


    public function test_veterinario_no_autorizado_a_crear_cabeza_ganado(): void
    {
        $this->cambiarRol($this->user);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson('api/ganado', $this->cabeza_ganado);

        $response->assertStatus(403);
    }

    public function test_veterinario_no_autorizado_a_actualizar_cabeza_ganado(): void
    {
        $this->cambiarRol($this->user);

        $cabezasGanado = $this->generarGanado();
        $idRandom = random_int(0, $this->cantidad_ganado - 1);
        $idGanadoEditar = $cabezasGanado[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(sprintf('api/ganado/%s', $idGanadoEditar), $this->cabeza_ganado);

        $response->assertStatus(403);
    }


    public function test_veterinario_no_autorizado_a_eliminar_cabeza_ganado(): void
    {
        $this->cambiarRol($this->user);

        $cabezasGanado = $this->generarGanado();
        $idRandom = random_int(0, $this->cantidad_ganado - 1);
        $idToDelete = $cabezasGanado[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->deleteJson(sprintf('api/ganado/%s', $idToDelete));

        $response->assertStatus(403);
    }
}
