<?php

namespace Tests\Feature;

use App\Models\CausasFallecimiento;
use App\Models\Comprador;
use App\Models\Ganado;
use App\Models\GanadoDescarte;
use Carbon\Carbon;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;
use Illuminate\Support\Str;
use Tests\Feature\Common\NeedsEstado;
use Tests\Feature\Common\NeedsGanado;
use Tests\Feature\Common\NeedsGanadoDescarte;
use Tests\Feature\Common\NeedsHacienda;
use Tests\Feature\Common\NeedsPersonal;
use Tests\Feature\Common\NeedsSetupRequest;

class GanadoDescarteTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use NeedsSetupRequest,
    NeedsPersonal,
    NeedsGanado,
    NeedsEstado,
    NeedsGanadoDescarte,
    NeedsHacienda {
    NeedsSetupRequest::setUp as needsSetupRequestSetUp;
    NeedsEstado::setUp as needsEstadoSetUp;
    }

    private array $ganadoDescarte = [
        'nombre' => 'descarte',
        'numero' => 350,
        'origen_id' => 1,
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
        'origen_id' => 2,
        'fecha_nacimiento' => '2010-02-17',
        'fecha_ingreso' => '2020-02-17',
        'peso_nacimiento' => 50,
        'peso_destete' => 70,
        'peso_2year' => 90,
        'peso_actual' => 100,
    ];

    private int $cantidad_ganadoDescarte = 10;

    private $descarte_fallecido;
    private $descarte_vendido;


    protected function setUp(): void
    {
        $this->needsSetupRequestSetUp();
        $this->needsEstadoSetUp();
        $this->generarGanado();


            $comprador = Comprador::factory()->for($this->hacienda)->create()->id;
            $causaFallecimiento = CausasFallecimiento::factory()->create();
            $this->descarte_fallecido = array_merge($this->ganadoDescarte, ['estado_id' => [2,3,4],'fecha_fallecimiento' => '2020-10-02','descripcion'=>'tyes','causas_fallecimiento_id'=>$causaFallecimiento->id]);
            $this->descarte_vendido = array_merge($this->ganadoDescarte, ['estado_id' => [5,6,7],'fecha_venta' => '2020-10-02','precio' => 100,'comprador_id' => $comprador]);
            $this->ganadoDescarte = array_merge($this->ganadoDescarte, ['estado_id' => [1]]);
    }


    public static function ErrorInputProvider(): array
    {
        return [
            'caso de que exista el nombre o numero' => [
                [
                    'nombre' => 'test',
                    'numero' => 299,
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
                    'fecha_nacimiento' => '2015-13-02',
                    'estado_id' => [1],
                ], [
                    'nombre', 'numero', 'origen_id', 'fecha_nacimiento',
                ]
            ],
            'caso de no insertar datos requeridos' => [
                ['estado_id' => [1],], ['nombre']
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

    public function test_obtener_ganadoDescartes(): void
    {
        $this->generarGanadoDescartes();

        $response = $this->setUpRequest()->getJson('api/ganado_descarte');

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'ganado_descartes',
                    $this->cantidad_ganadoDescarte,
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
                            'fallecimiento' => 'array|null',
                            'venta' => 'array|null',
                        ])
                        ->where('sexo', 'M')
                        ->where('tipo', fn (string $tipoGanado) => Str::contains($tipoGanado, ['Becerro', 'Maute','Novillo','Adulto']))
                )
            );
    }


    public function test_creacion_ganadoDescarte(): void
    {

        $response = $this->setUpRequest()->postJson('api/ganado_descarte', $this->ganadoDescarte);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'ganado_descarte',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string',
                            'numero' => 'integer|null',
                            'origen' => 'string',
                            'tipo' => 'string',
                            'fecha_nacimiento' => 'string',
                            'fecha_ingreso' => 'string|null',
                            'ganado_id' => 'integer',
                            'estados' => 'array',
                            'pesos' => 'array|null',
                            'fallecimiento' => 'array|null',
                            'venta' => 'array|null',

                        ])
                        ->where('sexo', 'M')
                    ->where('tipo', fn (string $tipoGanado) => Str::contains($tipoGanado, ['Becerro', 'Maute','Novillo','Adulto']))
                )
            );
    }


    public function test_creacion_ganadoDescarte_externo(): void
    {
        //datos que hacen referencia a que el ganado descarte es de origen externo
        $this->ganadoDescarte['origen_id'] = 2;
        $this->ganadoDescarte['fecha_ingreso'] = '2020-02-17';

        $response = $this->setUpRequest()->postJson('api/ganado_descarte', $this->ganadoDescarte);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'ganado_descarte',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string',
                            'numero' => 'integer|null',
                            'origen' => 'string',
                            'tipo' => 'string',
                            'fecha_nacimiento' => 'string',
                            'ganado_id' => 'integer',
                            'estados' => 'array',
                            'pesos' => 'array|null',
                            'sexo' => 'string',
                            'tipo' => 'string',
                            'fallecimiento' => 'array|null',
                            'venta' => 'array|null',

                        ])
                        ->where('origen', 'Externo')
                        ->where('fecha_ingreso',Carbon::parse( $this->ganadoDescarte['fecha_ingreso'])->format('d-m-Y'))
                )
            );
    }

    public function test_creacion_descarte_fallecido(): void
    {
        $response = $this->setUpRequest()->postJson('api/ganado_descarte', $this->descarte_fallecido);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                $json->has(
                    'ganado_descarte',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json
                        ->where('estados.0.estado', 'fallecido')
                         ->has('fallecimiento',
                        fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson=>
                        $json->where('fecha', Carbon::parse( $this->descarte_fallecido['fecha_fallecimiento'])->format('d-m-Y'))
                        ->where('descripcion', $this->descarte_fallecido['descripcion'])
                         ->whereType('causa','string')
                    )
                        ->etc()
                )

            );
    }

    public function test_creacion_descarte_vendido(): void
    {
        $response = $this->setUpRequest()->postJson('api/ganado_descarte', $this->descarte_vendido);

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

        $response = $this->setUpRequest()->postJson('api/descartar_ganado', ['ganado_id' => $this->ganado->id]);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'ganado_descarte',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string',
                            'sexo' => 'string',
                            'numero' => 'integer|null',
                            'origen' => 'string',
                            'tipo' => 'string',
                            'fecha_nacimiento' => 'string',
                            'fecha_ingreso' => 'string|null',
                            'ganado_id' => 'integer',
                            'estados' => 'array',
                            'pesos' => 'array|null',
                            'fallecimiento' => 'array|null',
                            'venta' => 'array|null',

                        ])
                    ->where('tipo', fn (string $tipoGanado) => Str::contains($tipoGanado, ['Becerro', 'Maute','Novillo','Adulto']))
                    ->has('estados', 1)
                )
            );
    }


    public function test_obtener_ganadoDescarte(): void
    {
        $ress = $this->generarGanadoDescartes();
        $idRandom = random_int(0, $this->cantidad_ganadoDescarte - 1);
        $idRes = $ress[$idRandom]->id;


        $response = $this->setUpRequest()->getJson(sprintf('api/ganado_descarte/%s', $idRes));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'ganado_descarte',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string',
                            'numero' => 'integer|null',
                            'sexo'=>'string',
                            'origen' => 'string',
                            'fecha_nacimiento' => 'string',
                            'fecha_ingreso' => 'string|null',
                            'ganado_id' => 'integer',
                            'estados' => 'array',
                            'pesos' => 'array|null',
                            'fallecimiento' => 'array|null',
                            'venta' => 'array|null',

                        ])
                        ->where('sexo', 'M')
                    ->where('tipo', fn (string $tipoGanado) => Str::contains($tipoGanado, ['Becerro', 'Maute','Novillo','Adulto']))
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
        $ganadoDescarteActual = $this->generarGanadoDescarte();

        $response = $this->setUpRequest()->putJson(sprintf('api/ganado_descarte/%s', $ganadoDescarteActual->id), $this->ganadoDescarteActualizado);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json
                ->where('ganado_descarte.nombre', $this->ganadoDescarteActualizado['nombre'])
                ->where('ganado_descarte.numero', $ganadoDescarteActual['ganado']['numero'])
                ->where('ganado_descarte.origen', 'Externo') //origen_id = 2
                ->where('ganado_descarte.sexo', $ganadoDescarteActual['ganado']['sexo'])
                ->where('ganado_descarte.pesos.peso_nacimiento', $this->ganadoDescarteActualizado['peso_nacimiento'] . 'KG')
                ->where('ganado_descarte.pesos.peso_destete', $this->ganadoDescarteActualizado['peso_destete'] . 'KG')
                ->where('ganado_descarte.pesos.peso_2year', $this->ganadoDescarteActualizado['peso_2year'] . 'KG')
                ->where('ganado_descarte.pesos.peso_actual', $this->ganadoDescarteActualizado['peso_actual'] . 'KG')
                ->where('ganado_descarte.fecha_nacimiento', Carbon::parse( $this->ganadoDescarteActualizado['fecha_nacimiento'])->format('d-m-Y'))
                ->where('ganado_descarte.fecha_ingreso', Carbon::parse( $this->ganadoDescarteActualizado['fecha_ingreso'])->format('d-m-Y'))
                ->where('ganado_descarte.tipo', fn (string $tipoGanado) => Str::contains($tipoGanado, ['Becerro', 'Maute','Novillo','Adulto']))
                ->etc()
        );
    }

    public function test_actualizar_res_con_otro_existente_repitiendo_campos_unicos(): void
    {
        $this->generarGanadoDescarte();

        $ganadoDescarte = $this->generarGanadoDescartes();
        $idRandom = random_int(0, $this->cantidad_ganadoDescarte - 1);
        $idResEditar = $ganadoDescarte[$idRandom]->id;

        $response = $this->setUpRequest()->putJson(sprintf('api/ganado_descarte/%s', $idResEditar), $this->ganadoDescarte);

        $response->assertStatus(422)->assertJson(fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
        $json->hasAll(['errors.nombre', 'errors.numero'])
        ->etc());
    }

    public function test_actualizar_res_sin_modificar_campos_unicos(): void
    {
        $ganadoDescarte = $this->generarGanadoDescarte();

        $response = $this->setUpRequest()->putJson(sprintf('api/ganado_descarte/%s', $ganadoDescarte->id), $this->ganadoDescarte);

        $response->assertStatus(200)->assertJson(['ganado_descarte' => true]);
    }


    public function test_eliminar_res(): void
    {
        $ress = $this->generarGanadoDescartes();
        $idRandom = random_int(0, $this->cantidad_ganadoDescarte - 1);
        $idToDelete = $ress[$idRandom]->id;


        $response = $this->setUpRequest()->deleteJson(sprintf('api/ganado_descarte/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['ganado_descarteID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_res(array $ganadoDescarte, array $errores): void
    {
        GanadoDescarte::factory()
            ->for($this->hacienda)
            ->for(Ganado::factory()->for($this->hacienda)->create(['nombre' => 'testg', 'numero' => 299]))
            ->create();

        $response = $this->setUpRequest()->postJson('api/ganado_descarte', $ganadoDescarte);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_autorizacion_maniupular__res_otro_usuario(): void
    {
        $this->crearOtraHacienda();

        $resOtroHacienda = GanadoDescarte::factory()
            ->for($this->otraHacienda)
            ->for(Ganado::factory()->for($this->otraHacienda))
            ->create();

        $idResOtroHacienda = $resOtroHacienda->id;

        $this->generarGanadoDescartes();

        $response = $this->setUpRequest()->putJson(sprintf('api/ganado_descarte/%s', $idResOtroHacienda), $this->ganadoDescarte);

        $response->assertStatus(403);
    }


    public function test_veterinario_no_autorizado_a_crear_ganado_descarte(): void
    {
        $response = $this->cambiarRol($this->user)
        ->setUpRequest()
        ->postJson('api/ganado_descarte', $this->ganadoDescarte);

        $response->assertStatus(403);
    }

    public function test_veterinario_no_autorizado_a_actualizar_ganado_descarte(): void
    {

        $cabezasGanadoDescarte = $this->generarGanadoDescartes();
        $idRandom = random_int(0, $this->cantidad_ganadoDescarte - 1);
        $idGanadoEditar = $cabezasGanadoDescarte[$idRandom]->id;

        $response = $this->cambiarRol($this->user)
        ->setUpRequest()
        ->putJson(sprintf('api/ganado_descarte/%s', $idGanadoEditar), $this->ganadoDescarte);

        $response->assertStatus(403);
    }


    public function test_veterinario_no_autorizado_a_eliminar_ganado_descarte(): void
    {

        $cabezasGanadoDescarte = $this->generarGanadoDescartes();
        $idRandom = random_int(0, $this->cantidad_ganadoDescarte - 1);
        $idEliminar = $cabezasGanadoDescarte[$idRandom]->id;


        $response = $this->cambiarRol($this->user)->setUpRequest()->deleteJson(sprintf('api/ganado_descarte/%s', $idEliminar));

        $response->assertStatus(403);
    }
}
