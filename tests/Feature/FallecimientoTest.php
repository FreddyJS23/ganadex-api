<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Fallecimiento;
use App\Models\Finca;
use App\Models\Ganado;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class FallecimientoTest extends TestCase
{
    use RefreshDatabase;

    private array $fallecimiento = [
        'causa' => 'enferma',
        'fecha' => '2020-10-02',
    ];

    private int $cantidad_fallecimientos = 10;
    private $estado;
    private $user;
    private $finca;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();

            $this->finca
            = Finca::factory()
            ->for($this->user)
            ->create();

        $this->estado = Estado::all();
    }

    private function generarFallecimiento(): Collection
    {
        return Fallecimiento::factory()
            ->count($this->cantidad_fallecimientos)
            ->for(Ganado::factory()->for($this->finca)->hasAttached($this->estado))
            ->create();
    }
    public static function ErrorInputProvider(): array
    {
        return [

            'caso de insertar datos erróneos' => [
                [
                    'causa' => 'te',
                    'ganado_id' => 'hj',

                ], ['causa', 'ganado_id']
            ],
            'caso de no insertar datos requeridos' => [
                [], ['causa', 'ganado_id']
            ],
            'caso de inserta numero ganado inexistente' => [
                [
                    'causa' => 'enferma',
                    'ganado_id' => 0
                ], ['ganado_id']
            ],
        ];
    }


    /**
     * A basic feature test example.
     */

    public function test_obtener_cabezas_ganados_fallecidas(): void
    {
        $this->generarFallecimiento();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->getJson('api/fallecimientos');

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->whereType('fallecidos', 'array')
                ->has('fallecidos',$this->cantidad_fallecimientos)
                ->has(
                    'fallecidos.0',
                    fn (AssertableJson $json)
                    => $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'causa' => 'string',
                    ])
                    ->has(
                    'ganado',
                    fn (AssertableJson $json)
                    => $json->whereAllType(['id' => 'integer', 'numero' => 'integer'])
                )
                )
        );
    }


    public function test_creacion_fallecimiento(): void
    {
        $ganado = Ganado::factory()
            ->hasPeso(1)
            ->hasAttached($this->estado)
            ->for($this->finca)
            ->create();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->postJson('api/fallecimientos', $this->fallecimiento + ['ganado_id' => $ganado->id]);

        $response->assertStatus(201)->assertJson(
            fn (AssertableJson $json) => $json->whereAllType([
                'fallecimiento.id' => 'integer',
                'fallecimiento.fecha' => 'string',
                'fallecimiento.causa' => 'string',
            ])->has(
                'fallecimiento.ganado',
                fn (AssertableJson $json)
                => $json->whereAllType(['id' => 'integer', 'numero' => 'integer'])
            )
        );
    }


    public function test_obtener_fallecimiento(): void
    {
        $fallecimientos = $this->generarFallecimiento();
        $idRandom = rand(0, $this->cantidad_fallecimientos - 1);
        $idfallecimientos = $fallecimientos[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->getJson(sprintf('api/fallecimientos/%s', $idfallecimientos), $this->fallecimiento);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->whereAllType([
                'fallecimiento.id' => 'integer',
                'fallecimiento.fecha' => 'string',
                'fallecimiento.causa' => 'string',
            ])->has(
                'fallecimiento.ganado',
                fn (AssertableJson $json)
                => $json->whereAllType(['id' => 'integer', 'numero' => 'integer'])
            )
        );
    }

    public function test_actualizar_fallecimiento(): void
    {
        $fallecimientos = $this->generarFallecimiento();
        $idRandom = rand(0, $this->cantidad_fallecimientos - 1);
        $idfallecimientosEditar = $fallecimientos[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->putJson(sprintf('api/fallecimientos/%s', $idfallecimientosEditar), $this->fallecimiento);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->where('fallecimiento.causa', $this->fallecimiento['causa'])
                ->where('fallecimiento.fecha', $this->fallecimiento['fecha'])
                ->whereAllType([
                    'fallecimiento.id' => 'integer',
                    'fallecimiento.fecha' => 'string',
                    'fallecimiento.causa' => 'string',
                ])->has(
                    'fallecimiento.ganado',
                    fn (AssertableJson $json)
                    => $json->whereAllType(['id' => 'integer', 'numero' => 'integer'])
                )
                ->etc()
        );
    }


    public function test_eliminar_fallecimiento(): void
    {
        $fallecimientos = $this->generarFallecimiento();
        $idRandom = rand(0, $this->cantidad_fallecimientos - 1);
        $idToDelete = $fallecimientos[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->deleteJson(sprintf('api/fallecimientos/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['fallecimientoID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_fallecimiento($fallecimientos, $errores): void
    {
        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->postJson('api/fallecimientos', $fallecimientos);

        $response->assertStatus(422)->assertInvalid($errores);
    }
}
