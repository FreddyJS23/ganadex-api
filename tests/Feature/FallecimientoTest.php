<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Fallecimiento;
use App\Models\Ganado;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class FallecimientoTest extends TestCase
{
    use RefreshDatabase;

    private array $fallecimiento = [
        'causa' => 'enferma',
        'fecha'=>'2020-10-02',
    ];

    private int $cantidad_fallecimientos = 10;
    private $estado;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();
        
            $this->estado = Estado::all();
    }

    private function generarFallecimiento(): Collection
    {
        return Fallecimiento::factory()
            ->count($this->cantidad_fallecimientos)
            ->for(Ganado::factory()->for($this->user)->hasAttached($this->estado))
            ->create();
    }
    public static function ErrorInputProvider(): array
    {
        return [
            
            'caso de insertar datos erróneos' => [
                [
                    'causa' => 'te',
                    'numero_ganado' => 'hj',
                 
                ], ['causa','numero_ganado']
            ],
            'caso de no insertar datos requeridos' => [
                [ ], ['causa','numero_ganado']
            ],
            'caso de inserta numero ganado inexistente' => [
                [
                    'causa'=>'enferma',
                    'numero_ganado'=> 0
                 ], ['numero_ganado']
            ],
        ];
    }


    /**
     * A basic feature test example.
     */

    public function test_obtener_cabezas_ganados_fallecidas(): void
    {
        $this->generarFallecimiento();

        $response = $this->actingAs($this->user)->getJson('api/fallecimientos');
        $response->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->has('fallecidos', $this->cantidad_fallecimientos));
    }


    public function test_creacion_fallecimiento(): void
    {
        $ganado=Ganado::factory()
            ->hasPeso(1)
            ->hasAttached($this->estado)
            ->for($this->user)
            ->create();

        $response = $this->actingAs($this->user)->postJson('api/fallecimientos', $this->fallecimiento + ['numero_ganado'=>$ganado->numero]);

        $response->assertStatus(201)->assertJson(['fallecimiento' => true]);
    }


    public function test_obtener_fallecimiento(): void
    {
        $fallecimientos = $this->generarFallecimiento();
        $idRandom = rand(0, $this->cantidad_fallecimientos - 1);
        $idfallecimientos = $fallecimientos[$idRandom]->id;

        $response = $this->actingAs($this->user)->getJson(sprintf('api/fallecimientos/%s', $idfallecimientos), $this->fallecimiento);

        $response->assertStatus(200)->assertJson(['fallecimiento' => true]);
    }

    public function test_actualizar_fallecimiento(): void
    {
        $fallecimientos = $this->generarFallecimiento();
        $idRandom = rand(0, $this->cantidad_fallecimientos - 1);
        $idfallecimientosEditar = $fallecimientos[$idRandom]->id;

        $response = $this->actingAs($this->user)->putJson(sprintf('api/fallecimientos/%s', $idfallecimientosEditar), $this->fallecimiento);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->where('fallecimiento.causa', $this->fallecimiento['causa'])
                ->where('fallecimiento.fecha', $this->fallecimiento['fecha'])
                ->etc()
        );
    }


    public function test_eliminar_fallecimiento(): void
    {
        $fallecimientos = $this->generarFallecimiento();
        $idRandom = rand(0, $this->cantidad_fallecimientos - 1);
        $idToDelete = $fallecimientos[$idRandom]->id;


        $response = $this->actingAs($this->user)->deleteJson(sprintf('api/fallecimientos/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['fallecimientoID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_fallecimiento($fallecimientos, $errores): void
    {
        $response = $this->actingAs($this->user)->postJson('api/fallecimientos', $fallecimientos);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    
}
