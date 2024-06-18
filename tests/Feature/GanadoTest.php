<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Ganado;
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
        'peso_nacimiento' => '30KG',
        'peso_destete' => '130KG',
        'peso_2year' => '300KG',
        'peso_actual' => '600KG',
        'estado_id' => [1],
    ];

    private int $cantidad_ganado = 10;
    private $estado;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();
        $this->estado = Estado::all();
    }

    private function generarGanado(): Collection
    {
        return Ganado::factory()
            ->count($this->cantidad_ganado)
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->user)
            ->create();
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
                    'peso_nacimiento' => '30KG',
                    'peso_destete' => '30KG',
                    'peso_2year' => '30KG',
                    'peso_actual' => '30KG',
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
                    'peso_nacimiento' => '30KG',
                    'peso_destete' => '30KG',
                    'peso_2year' => '30KG',
                    'peso_actual' => '30KG',
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

        $response = $this->actingAs($this->user)->getJson('api/ganado');
        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has('cabezas_ganado', $this->cantidad_ganado)
                    ->has(
                        'cabezas_ganado.0',
                        fn (AssertableJson $json) =>
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
                    fn (AssertableJson $json) => $json
                        ->whereAllType([
                            'id'=>'integer',
                            'peso_nacimiento' => 'string',
                            'peso_destete' => 'string',
                            'peso_2year' => 'string',
                            'peso_actual' => 'string',
                        ])
                )
                ->has(
                    'eventos',
                    fn (AssertableJson $json) => $json
                        ->whereAllType([
                            'id'=>'integer',
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

        $response = $this->actingAs($this->user)->postJson('api/ganado', $this->cabeza_ganado);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has(
                        'ganado',
                        fn (AssertableJson $json) =>
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
                            ->has('pesos',
                            fn(AssertableJson $json)=>$json
                            ->whereAllType([
                                 'id'=>'integer',      
                                'peso_nacimiento' => 'string',
                                        'peso_destete' => 'string',
                                        'peso_2year' => 'string',
                                        'peso_actual' => 'string',]))
                            ->has('eventos',
                            fn(AssertableJson $json)=>$json
                            ->whereAllType([
                         'id'=>'integer',
                                'prox_revision' => 'string|null',
                      
                        'prox_parto' => 'string|null',
                        'prox_secado' => 'string|null',]))
                            )
            );
    }


    public function test_obtener_cabeza_ganado(): void
    {
        $cabezasGanado = $this->generarGanado();
        $idRandom = rand(0, $this->cantidad_ganado - 1);
        $idGanado = $cabezasGanado[$idRandom]->id;

        $response = $this->actingAs($this->user)->getJson(sprintf('api/ganado/%s', $idGanado), $this->cabeza_ganado);

        $response->assertStatus(200)->assertJson(['ganado' => true]);
    }

    public function test_actualizar_cabeza_ganado(): void
    {
        $cabezasGanado = $this->generarGanado();
        $idRandom = rand(0, $this->cantidad_ganado - 1);
        $idGanadoEditar = $cabezasGanado[$idRandom]->id;

        $response = $this->actingAs($this->user)->putJson(sprintf('api/ganado/%s', $idGanadoEditar), $this->cabeza_ganado);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->where('ganado.nombre', $this->cabeza_ganado['nombre'])
                ->where('ganado.numero', $this->cabeza_ganado['numero'])
                ->where('ganado.origen', $this->cabeza_ganado['origen'])
                ->where('ganado.sexo', $this->cabeza_ganado['sexo'])
                ->where('ganado.fecha_nacimiento', $this->cabeza_ganado['fecha_nacimiento'])
                ->where('ganado.pesos.peso_nacimiento', $this->cabeza_ganado['peso_nacimiento'])
                ->where('ganado.pesos.peso_destete', $this->cabeza_ganado['peso_destete'])
                ->where('ganado.pesos.peso_2year', $this->cabeza_ganado['peso_2year'])
                ->where('ganado.pesos.peso_actual', $this->cabeza_ganado['peso_actual'])

                ->etc()
        );
    }

    public function test_actualizar_cabeza_ganado_con_otro_existente_repitiendo_campos_unicos(): void
    {
        Ganado::factory()->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->user)
            ->create(['nombre' => 'test', 'numero' => 392]);

        $cabezasGanado = $this->generarGanado();
        $idRandom = rand(0, $this->cantidad_ganado - 1);
        $idGanadoEditar = $cabezasGanado[$idRandom]->id;

        $response = $this->actingAs($this->user)->putJson(sprintf('api/ganado/%s', $idGanadoEditar), $this->cabeza_ganado);

        $response->assertStatus(422)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['errors.nombre', 'errors.numero'])
            ->etc());
    }

    public function test_actualizar_cabeza_ganado_sin_modificar_campos_unicos(): void
    {
        $ganado = Ganado::factory()->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->user)
            ->create(['nombre' => 'test', 'numero' => 392]);

        $response = $this->actingAs($this->user)->putJson(sprintf('api/ganado/%s', $ganado->id), $this->cabeza_ganado);

        $response->assertStatus(200)->assertJson(['ganado' => true]);
    }

    public function test_eliminar_cabeza_ganado(): void
    {
        $cabezasGanado = $this->generarGanado();
        $idRandom = rand(0, $this->cantidad_ganado - 1);
        $idToDelete = $cabezasGanado[$idRandom]->id;


        $response = $this->actingAs($this->user)->deleteJson(sprintf('api/ganado/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['ganadoID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_cabeza_ganado($ganado, $errores): void
    {
        Ganado::factory()->for($this->user)->create(['nombre' => 'test', 'numero' => 300]);

        $response = $this->actingAs($this->user)->postJson('api/ganado', $ganado);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_autorizacion_maniupular__cabeza_ganado_otro_usuario(): void
    {
        $otroUsuario = User::factory()->create();

        $ganadoOtroUsuario = Ganado::factory()
            ->hasPeso(1)->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($otroUsuario)
            ->create();

        $idGanadoOtroUsuario = $ganadoOtroUsuario->id;

        $this->generarGanado();

        $response = $this->actingAs($this->user)->putJson(sprintf('api/ganado/%s', $idGanadoOtroUsuario), $this->cabeza_ganado);

        $response->assertStatus(403);
    }
}
