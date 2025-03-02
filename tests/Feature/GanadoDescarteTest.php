<?php

namespace Tests\Feature;

use App\Models\CausasFallecimiento;
use App\Models\Comprador;
use App\Models\Estado;
use App\Models\Hacienda;
use App\Models\Ganado;
use App\Models\GanadoDescarte;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;
use Illuminate\Support\Str;

class GanadoDescarteTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    private array $ganadoDescarte = [
        'nombre' => 'test',
        'numero' => 392,
        'origen' => 'local',
        'sexo' => 'M',
        'fecha_nacimiento' => '2015-02-17',
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

    private array $ganadoDescarteActualizado = [
        'nombre' => 'actualizado',
        'origen' => 'externo',
        'fecha_nacimiento' => '2010-02-17',
        'peso_nacimiento' => 50,
        'peso_destete' => 70,
        'peso_2year' => 90,
        'peso_actual' => 100,
    ];

    private int $cantidad_ganadoDescarte = 10;

    private $user;
    private $hacienda;
    private $descarte_fallecido;
    private $descarte_vendido;
    private $estado;


    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->hasConfiguracion()->create();

        $this->user->assignRole('admin');
        $this->estado = Estado::all();

            $this->hacienda
            = Hacienda::factory()
            ->for($this->user)
            ->create();

            $comprador = Comprador::factory()->for($this->hacienda)->create()->id;
            $causaFallecimiento = CausasFallecimiento::factory()->create();
            $this->descarte_fallecido = array_merge($this->ganadoDescarte, ['estado_id' => [2,3,4],'fecha_fallecimiento' => '2020-10-02','descripcion'=>'tyes','causas_fallecimiento_id'=>$causaFallecimiento->id]);
            $this->descarte_vendido = array_merge($this->ganadoDescarte, ['estado_id' => [5,6,7],'fecha_venta' => '2020-10-02','precio' => 100,'comprador_id' => $comprador]);
            $this->ganadoDescarte = array_merge($this->ganadoDescarte, ['estado_id' => [1]]);
    }

    private function generarGanadoDescartes(): Collection
    {
        return GanadoDescarte::factory()
            ->count(10)
            ->for($this->hacienda)
            ->for(Ganado::factory()->hasVacunaciones(3, ['hacienda_id' => $this->hacienda->id])->create( ['hacienda_id' => $this->hacienda->id, 'sexo' => 'M', 'tipo_id' => 4])  )
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
                    'fecha_nacimiento' => '2015-13-02',
                    'estado_id' => [1],
                ], [
                    'nombre', 'numero', 'origen', 'fecha_nacimiento',
                ]
            ],
            'caso de no insertar datos requeridos' => [
                ['origen' => 'local','estado_id' => [1],], ['nombre']
            ],
        ];
    }



    /**
     * A basic feature test example.
     */

    public function test_obtener_ganadoDescartes(): void
    {
        $this->generarGanadoDescartes();

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson('api/ganado_descarte');

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'ganado_descartes',
                    $this->cantidad_ganadoDescarte,
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string',
                            'numero' => 'integer',
                            'origen' => 'string',
                            'fecha_nacimiento' => 'string',
                            'ganado_id' => 'integer',
                            'estados' => 'array',
                            'pesos' => 'array|null',
                        ])
                        ->where('sexo', 'M')
                        ->where('tipo', fn (string $tipoGanado) => Str::contains($tipoGanado, ['becerro', 'maute','novillo','adulto']))
                )
            );
    }


    public function test_creacion_ganadoDescarte(): void
    {

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson('api/ganado_descarte', $this->ganadoDescarte);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'ganado_descarte',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string',
                            'numero' => 'integer',
                            'origen' => 'string',
                            'tipo' => 'string',
                            'fecha_nacimiento' => 'string',
                            'ganado_id' => 'integer',
                            'estados' => 'array',
                            'pesos' => 'array|null',
                        ])
                        ->where('sexo', 'M')
                    ->where('tipo', fn (string $tipoGanado) => Str::contains($tipoGanado, ['becerro', 'maute', 'novillo', 'adulto']))
                )
            );
    }

    public function test_creacion_descarte_fallecido(): void
    {
        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson('api/ganado_descarte', $this->descarte_fallecido);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                $json->has(
                    'ganado_descarte',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json
                        ->where('estados.0.estado', 'fallecido')
                        ->etc()
                )
            );
    }

    public function test_creacion_descarte_vendido(): void
    {
        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson('api/ganado_descarte', $this->descarte_vendido);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                $json->has(
                    'ganado_descarte',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json
                        ->where('estados.0.estado', 'vendido')
                        ->etc()
                )
            );
    }



    public function test_descartar_ganado(): void
    {
        $ganado = Ganado::factory()
            ->hasPeso(1)
            ->hasEvento(1)
            ->for($this->hacienda)
            ->create();

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson('api/descartar_ganado', ['ganado_id' => $ganado->id]);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'ganado_descarte',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string',
                            'sexo' => 'string',
                            'numero' => 'integer',
                            'origen' => 'string',
                            'tipo' => 'string',
                            'fecha_nacimiento' => 'string',
                            'ganado_id' => 'integer',
                            'estados' => 'array',
                            'pesos' => 'array|null',
                        ])
                    ->where('tipo', fn (string $tipoGanado) => Str::contains($tipoGanado, ['becerro', 'maute', 'novillo', 'adulto']))
                )
            );
    }


    public function test_obtener_ganadoDescarte(): void
    {
        $ress = $this->generarGanadoDescartes();
        $idRandom = random_int(0, $this->cantidad_ganadoDescarte - 1);
        $idRes = $ress[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/ganado_descarte/%s', $idRes));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'ganado_descarte',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string',
                            'numero' => 'integer',
                            'sexo'=>'string',
                            'origen' => 'string',
                            'fecha_nacimiento' => 'string',
                            'ganado_id' => 'integer',
                            'estados' => 'array',
                            'pesos' => 'array|null',
                        ])
                        ->where('sexo', 'M')
                    ->where('tipo', fn (string $tipoGanado) => Str::contains($tipoGanado, ['becerro', 'maute', 'novillo', 'adulto']))
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

    public function test_actualizar_ganadoDescarte(): void
    {
        $ganadoDescarteActual = GanadoDescarte::factory()
        ->for($this->hacienda)
        ->for(Ganado::factory()->hasPeso()->create(['hacienda_id' => $this->hacienda->id,
        'sexo' => 'M',
        'tipo_id' => 4,
        'nombre' => 'test',
        'numero' => 392,
        'origen' => 'local',
        'fecha_nacimiento' => '2015-02-17']))
        ->create();

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(sprintf('api/ganado_descarte/%s', $ganadoDescarteActual->id), $this->ganadoDescarteActualizado);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json
                ->where('ganado_descarte.nombre', $this->ganadoDescarteActualizado['nombre'])
                ->where('ganado_descarte.numero', $ganadoDescarteActual['ganado']['numero'])
                ->where('ganado_descarte.origen', $this->ganadoDescarteActualizado['origen'])
                ->where('ganado_descarte.sexo', $ganadoDescarteActual['ganado']['sexo'])
                ->where('ganado_descarte.pesos.peso_nacimiento', $this->ganadoDescarteActualizado['peso_nacimiento'] . 'KG')
                ->where('ganado_descarte.pesos.peso_destete', $this->ganadoDescarteActualizado['peso_destete'] . 'KG')
                ->where('ganado_descarte.pesos.peso_2year', $this->ganadoDescarteActualizado['peso_2year'] . 'KG')
                ->where('ganado_descarte.pesos.peso_actual', $this->ganadoDescarteActualizado['peso_actual'] . 'KG')
                ->where('ganado_descarte.fecha_nacimiento', $this->ganadoDescarteActualizado['fecha_nacimiento'])
                ->where('ganado_descarte.tipo', fn (string $tipoGanado) => Str::contains($tipoGanado, ['becerro', 'maute', 'novillo', 'adulto']))
                ->etc()
        );
    }

    public function test_actualizar_res_con_otro_existente_repitiendo_campos_unicos(): void
    {
        GanadoDescarte::factory()
            ->for($this->hacienda)
            ->for(Ganado::factory()->for($this->hacienda)->create(['nombre' => 'test', 'numero' => 392]))
            ->create();

        $ganadoDescarte = $this->generarGanadoDescartes();
        $idRandom = random_int(0, $this->cantidad_ganadoDescarte - 1);
        $idResEditar = $ganadoDescarte[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(sprintf('api/ganado_descarte/%s', $idResEditar), $this->ganadoDescarte);

        $response->assertStatus(422)->assertJson(fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
        $json->hasAll(['errors.nombre', 'errors.numero'])
        ->etc());
    }

    public function test_actualizar_res_sin_modificar_campos_unicos(): void
    {
        $ganadoDescarte = GanadoDescarte::factory()
            ->for($this->hacienda)
            ->for(Ganado::factory()->hasPeso()->for($this->hacienda)->create(['nombre' => 'test', 'numero' => 392]))
            ->create();

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(sprintf('api/ganado_descarte/%s', $ganadoDescarte->id), $this->ganadoDescarte);

        $response->assertStatus(200)->assertJson(['ganado_descarte' => true]);
    }


    public function test_eliminar_res(): void
    {
        $ress = $this->generarGanadoDescartes();
        $idRandom = random_int(0, $this->cantidad_ganadoDescarte - 1);
        $idToDelete = $ress[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->deleteJson(sprintf('api/ganado_descarte/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['ganado_descarteID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_res(array $ganadoDescarte, array $errores): void
    {
        GanadoDescarte::factory()
            ->for($this->hacienda)
            ->for(Ganado::factory()->for($this->hacienda)->create(['nombre' => 'test', 'numero' => 300]))
            ->create();

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson('api/ganado_descarte', $ganadoDescarte);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_autorizacion_maniupular__res_otro_usuario(): void
    {
        $otroHacienda = Hacienda::factory()
        ->for($this->user)
        ->create(['nombre' => 'otro_hacienda']);

        $resOtroHacienda = GanadoDescarte::factory()
            ->for($otroHacienda)
            ->for(Ganado::factory()->for($otroHacienda))
            ->create();

        $idResOtroHacienda = $resOtroHacienda->id;

        $this->generarGanadoDescartes();

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(sprintf('api/ganado_descarte/%s', $idResOtroHacienda), $this->ganadoDescarte);

        $response->assertStatus(403);
    }


    public function test_veterinario_no_autorizado_a_crear_ganado_descarte(): void
    {
        $this->cambiarRol($this->user);

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson('api/ganado_descarte', $this->ganadoDescarte);

        $response->assertStatus(403);
    }

    public function test_veterinario_no_autorizado_a_actualizar_ganado_descarte(): void
    {
        $this->cambiarRol($this->user);

        $cabezasGanadoDescarte = $this->generarGanadoDescartes();
        $idRandom = random_int(0, $this->cantidad_ganadoDescarte - 1);
        $idGanadoEditar = $cabezasGanadoDescarte[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(sprintf('api/ganado_descarte/%s', $idGanadoEditar), $this->ganadoDescarte);

        $response->assertStatus(403);
    }


    public function test_veterinario_no_autorizado_a_eliminar_ganado_descarte(): void
    {
        $this->cambiarRol($this->user);

        $cabezasGanadoDescarte = $this->generarGanadoDescartes();
        $idRandom = random_int(0, $this->cantidad_ganadoDescarte - 1);
        $idEliminar = $cabezasGanadoDescarte[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->deleteJson(sprintf('api/ganado_descarte/%s', $idEliminar));

        $response->assertStatus(403);
    }
}
