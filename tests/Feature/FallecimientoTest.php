<?php

namespace Tests\Feature;

use App\Models\CausasFallecimiento;
use App\Models\Estado;
use App\Models\Fallecimiento;
use App\Models\Hacienda;
use App\Models\Ganado;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\Common\NeedsEstado;
use Tests\Feature\Common\NeedsGanado;
use Tests\Feature\Common\NeedsSetupRequest;
use Tests\TestCase;

class FallecimientoTest extends TestCase
{
    use NeedsSetupRequest,
        NeedsGanado,
        NeedsEstado{
        NeedsSetupRequest::setUp as needsSetupRequestSetUp;
        NeedsEstado::setUp as needsEstadoSetUp;

    }

    private array $fallecimiento = [
        'fecha' => '2020-10-02',
        'descripcion' => 'test',
    ];
    private array $fallecimientoActualizado = [
        'fecha' => '2024-10-02',
        'descripcion' => 'test2',
    ];

    private int $cantidad_fallecimientos = 10;
    private $causaFallecimiento;

    protected function setUp(): void
    {
        $this->needsSetupRequestSetUp();
        $this->needsEstadoSetUp();
        $this->generarGanado();

        $this->causaFallecimiento = CausasFallecimiento::factory()->create();
    }

    private function generarFallecimiento(): Collection
    {
        return Fallecimiento::factory()
            ->count($this->cantidad_fallecimientos)
            ->for(Ganado::factory()->for($this->hacienda)->hasAttached($this->estado))
            ->create();
    }
    public static function ErrorInputProvider(): array
    {
        return [

            'caso de insertar datos erróneos' => [
                [
                    'ganado_id' => 'hj',
                    'descripcion' => 'hj',
                    'causas_fallecimiento_id' => 'hj',

                ], ['descripcion', 'causas_fallecimiento_id', 'ganado_id']
            ],
            'caso de no insertar datos requeridos' => [
                [], ['causas_fallecimiento_id', 'ganado_id']
            ],
            'caso de inserta numero ganado inexistente' => [
                [
                    'ganado_id' => 0,
                    'causas_fallecimiento_id'=>0
                ], ['ganado_id','causas_fallecimiento_id']
            ],
        ];
    }


    /**
     * A basic feature test example.
     */

    public function test_obtener_cabezas_ganados_fallecidas(): void
    {
        $this->generarFallecimiento();

        $response = $this->setUpRequest()->getJson('api/fallecimientos');

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json->whereType('fallecidos', 'array')
                ->has('fallecidos', $this->cantidad_fallecimientos)
                ->has(
                    'fallecidos.0',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                    => $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'causa' => 'string',
                        'descripcion' => 'string',
                    ])
                    ->has(
                        'ganado',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'numero' => 'integer|null'])
                    )
                )
        );
    }


    public function test_creacion_fallecimiento(): void
    {

        $response = $this->setUpRequest()->postJson('api/fallecimientos', $this->fallecimiento + ['ganado_id' => $this->ganado->id,'causas_fallecimiento_id'=>$this->causaFallecimiento->id]);

        $response->assertStatus(201)->assertJson(
            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType([
                'fallecimiento.id' => 'integer',
                'fallecimiento.fecha' => 'string',
                'fallecimiento.causa' => 'string',
            ])->has(
                'fallecimiento.ganado',
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                => $json->whereAllType(['id' => 'integer', 'numero' => 'integer|null'])
            )
        );
    }


    public function test_obtener_fallecimiento(): void
    {
        $fallecimientos = $this->generarFallecimiento();
        $idRandom = random_int(0, $this->cantidad_fallecimientos - 1);
        $idfallecimientos = $fallecimientos[$idRandom]->id;

        $response = $this->setUpRequest()->getJson(sprintf('api/fallecimientos/%s', $idfallecimientos), $this->fallecimiento);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType([
                'fallecimiento.id' => 'integer',
                'fallecimiento.fecha' => 'string',
                'fallecimiento.causa' => 'string',
            ])->has(
                'fallecimiento.ganado',
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                => $json->whereAllType(['id' => 'integer', 'numero' => 'integer|null'])
            )
        );
    }

    public function test_actualizar_fallecimiento(): void
    {
        $fallecimientos = $this->generarFallecimiento();
        $idRandom = random_int(0, $this->cantidad_fallecimientos - 1);
        $idfallecimientosEditar = $fallecimientos[$idRandom]->id;

        $response = $this->setUpRequest()->putJson(sprintf('api/fallecimientos/%s', $idfallecimientosEditar), $this->fallecimientoActualizado + ['causas_fallecimiento_id'=>$this->causaFallecimiento->id]);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json
                ->where('fallecimiento.fecha',Carbon::parse( $this->fallecimientoActualizado['fecha'])->format('d-m-Y'))
                ->where('fallecimiento.descripcion', $this->fallecimientoActualizado['descripcion'])
                ->whereAllType([
                    'fallecimiento.id' => 'integer',
                    'fallecimiento.fecha' => 'string',
                    'fallecimiento.causa' => 'string',
                ])->has(
                    'fallecimiento.ganado',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                    => $json->whereAllType(['id' => 'integer', 'numero' => 'integer|null'])
                )
                ->etc()
        );
    }


    public function test_eliminar_fallecimiento(): void
    {
        $fallecimientos = $this->generarFallecimiento();
        $idRandom = random_int(0, $this->cantidad_fallecimientos - 1);
        $idToDelete = $fallecimientos[$idRandom]->id;


        $response = $this->setUpRequest()->deleteJson(sprintf('api/fallecimientos/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['fallecimientoID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_fallecimiento(array $fallecimientos, array $errores): void
    {
        $response = $this->setUpRequest()->postJson('api/fallecimientos', $fallecimientos);

        $response->assertStatus(422)->assertInvalid($errores);
    }
}
