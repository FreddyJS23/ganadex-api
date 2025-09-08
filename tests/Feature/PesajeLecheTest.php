<?php

namespace Tests\Feature;

use App\Models\Ganado;
use App\Models\Leche;
use Illuminate\Database\Eloquent\Collection;
use Tests\Feature\Common\NeedsEstado;
use Tests\Feature\Common\NeedsGanado;
use Tests\Feature\Common\NeedsSetupRequest;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class PesajeLecheTest extends TestCase
{
     use NeedsSetupRequest,
    NeedsGanado,
    NeedsEstado{
    NeedsSetupRequest::setUp as needsSetupRequestSetUp;
    NeedsEstado::setUp as needsEstadoSetUp;
}

    private array $pesoLeche = [
        'peso_leche' => '99.99',
        'fecha' => '2020-10-02',


    ];

    private int $cantidad_pesoLeche = 10;

   
    private $ganadoFallecido;
    private $ganadoVendido;
    private $ganadoSano;
    private string $url;

    protected function setUp(): void
    {
    $this->needsSetupRequestSetUp();
    $this->needsEstadoSetUp();
    $this->generarGanado();

        $this->url = sprintf('api/ganado/%s/pesaje_leche', $this->ganado->id);
    }

    private function generarPesajesLeche(): Collection
    {
        return Leche::factory()
            ->count($this->cantidad_pesoLeche)
            ->for($this->ganado)
            ->for($this->hacienda)
            ->create();
    }

    public static function ErrorInputProvider(): array
    {
        return [

            'caso de insertar datos erróneos' => [
                [
                    'peso_leche' => 'dd33'
                ], ['peso_leche']
            ],
            'caso de no insertar datos requeridos' => [
                [], ['peso_leche']
            ],
        ];
    }



    /**
     * A basic feature test example.
     */

    public function test_obtener_todos_pesaje_de_leches(): void
    {

        $this->generarPesajesLeche();


        $response = $this->setUpRequest()->getJson($this->url);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'pesajes_leche',
                    $this->cantidad_pesoLeche,
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType([
                        'id' => 'integer',
                        'pesaje' => 'string',
                        'fecha' => 'string',
                    ])
                )
            );
    }


    public function test_creacion_pesaje_leche(): void
    {

        $response = $this->setUpRequest()->postJson($this->url, $this->pesoLeche);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'pesaje_leche',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType([
                        'id' => 'integer',
                        'pesaje' => 'string',
                        'fecha' => 'string',
                    ])
                )
            );
    }


    public function test_obtener_pesaje_leche(): void
    {
        $pesajesDeLeche = $this->generarPesajesLeche();

        $idRandom = random_int(0, $this->cantidad_pesoLeche - 1);
        $idPesoLeche = $pesajesDeLeche[$idRandom]->id;
        $response = $this->setUpRequest()->getJson(sprintf($this->url . '/%s', $idPesoLeche));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'pesaje_leche',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType([
                        'id' => 'integer',
                        'pesaje' => 'string',
                        'fecha' => 'string',
                    ])
                )
            );
    }
    public function test_actualizar_pesoLeche(): void
    {
        $pesajesDeLeche = $this->generarPesajesLeche();
        $idRandom = random_int(0, $this->cantidad_pesoLeche - 1);
        $idPesoLecheEditar = $pesajesDeLeche[$idRandom]->id;

        $response = $this->setUpRequest()->putJson(sprintf($this->url . '/%s', $idPesoLecheEditar), $this->pesoLeche);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'pesaje_leche',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->where('pesaje', $this->pesoLeche['peso_leche'])
                        ->etc()
                )
            );
    }

    public function test_eliminar_pesoLeche(): void
    {
        $pesajesDeLeche = $this->generarPesajesLeche();
        $idRandom = random_int(0, $this->cantidad_pesoLeche - 1);
        $idToDelete = $pesajesDeLeche[$idRandom]->id;


        $response = $this->setUpRequest()->deleteJson(sprintf($this->url . '/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['pesajeLecheID' => $idToDelete]);
    }

    public function test_obtener_pesajes_leche_de_todas_las_vacas(): void
    {
        Ganado::factory()
            ->count(10)
            ->hasPeso(1)
            ->has(Leche::factory()->for($this->hacienda)->count(3), 'pesajes_leche')
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->hacienda)
            ->create();

        $response = $this->setUpRequest()->getJson(route('todosPesajesLeche'));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has('todos_pesaje_leche.1', fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType([
                    'id' => 'integer',
                    'nombre' => 'string',
                    'numero' => 'integer|null',
                    'ultimo_pesaje' => 'string|null',
                    'pesaje_este_mes' => 'boolean',
                ]))
            );
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_pesoLeche(array $pesoLeche, array $errores): void
    {

        $response = $this->setUpRequest()->postJson($this->url, $pesoLeche);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_veterinario_no_autorizado_a_crear_pajuela_pesaje_leche(): void
    {
        $this->cambiarRol($this->user);

        $response = $this->setUpRequest()->postJson(route('pesaje_leche.store', ['ganado' => $this->ganado->id]), $this->pesoLeche);

        $response->assertStatus(403);
    }

    public function test_veterinario_no_autorizado_a_actualizar_pajuela_pesaje_leche(): void
    {
        $pajuelasToro = $this->generarPesajesLeche();
        $idRandom = random_int(0, $this->cantidad_pesoLeche - 1);
        $idPesoLecheEditar = $pajuelasToro[$idRandom]->id;

        $response = $this->cambiarRol($this->user)->setUpRequest()->putJson(route('pesaje_leche.update', ['ganado' => $this->ganado->id,'pesaje_leche' => $idPesoLecheEditar]), $this->pesoLeche);

        $response->assertStatus(403);
    }


    public function test_veterinario_no_autorizado_a_eliminar_pajuela_pesaje_leche(): void
    {

        $pajuelasToro = $this->generarPesajesLeche();
        $idRandom = random_int(0, $this->cantidad_pesoLeche - 1);
        $idPajuelaToroEliminar = $pajuelasToro[$idRandom]->id;

        $response = $this->cambiarRol($this->user)->setUpRequest()->deleteJson(route('pesaje_leche.destroy', ['ganado' => $this->ganado->id,'pesaje_leche' => $idPajuelaToroEliminar]));

        $response->assertStatus(403);
    }
}
