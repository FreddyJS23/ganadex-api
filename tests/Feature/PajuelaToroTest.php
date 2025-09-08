<?php

namespace Tests\Feature;

use App\Models\Hacienda;
use App\Models\PajuelaToro;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\Common\NeedsSetupRequest;
use Tests\TestCase;

class PajuelaToroTest extends TestCase
{
    use NeedsSetupRequest {
        NeedsSetupRequest::setUp as needsSetupRequestSetUp;
    }

    private array $pajuela_toro = [
        'codigo' => '21DDSQ7',
        'descripcion' => 'Toro de prueba',
        'fecha' => '2023-09-12',
    ];

    private int $cantidad_pajuelaToro = 10;


    protected function setUp(): void
    {
        $this->needsSetupRequestSetUp();
    }

    private function generarPajuelasToros(): Collection
    {
        return PajuelaToro::factory()
            ->count($this->cantidad_pajuelaToro)
            ->for($this->hacienda)
            ->create();
    }


    public static function ErrorInputProvider(): array
    {
        return [
            'caso de insertar datos erróneos' => [
                [
                    'codigo' => 33284,
                    'descripcion' => 231,
                    'fecha' => '09-12-2023',
                ], ['codigo', 'descripcion', 'fecha']
            ],
            'caso de no insertar datos requeridos' => [
                [],
                ['codigo','fecha']
            ],
        ];
    }


    /**
     * A basic feature test example.
     */

    public function test_obtener_todo_pajuelas_toro(): void
    {
        $this->generarPajuelasToros();

        $response = $this->setUpRequest()->getJson(route('pajuela_toros.index'));
        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'pajuela_toros',
                    $this->cantidad_pajuelaToro,
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType([
                        'id' => 'integer',
                        'codigo' => 'string',
                        'descripcion' => 'string',
                        'fecha' => 'string',
                    ])
                )
            );
    }


    public function test_creacion_pajuela_toro(): void
    {

        $response = $this->setUpRequest()->postJson(route('pajuela_toros.store'), $this->pajuela_toro);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'pajuela_toro',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType([
                        'id' => 'integer',
                        'codigo' => 'string',
                        'descripcion' => 'string',
                        'fecha' => 'string',
                    ])
                )
            );
    }


    public function test_obtener_pajuela_toro(): void
    {
        $pajuela_torols = $this->generarPajuelasToros();
        $idRandom = random_int(0, $this->cantidad_pajuelaToro - 1);
        $idPajuelaToro = $pajuela_torols[$idRandom]->id;

        $response = $this->setUpRequest()->getJson(route('pajuela_toros.show', ['pajuela_toro' => $idPajuelaToro]));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'pajuela_toro',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType([
                        'id' => 'integer',
                        'codigo' => 'string',
                        'descripcion' => 'string',
                        'fecha' => 'string',
                    ])
                )
            );
    }

    public function test_actualizar_pajuela_toro(): void
    {
        $pajuela_toro = $this->generarPajuelasToros();
        $idRandom = random_int(0, $this->cantidad_pajuelaToro - 1);
        $idPajuelaToroEditar = $pajuela_toro[$idRandom]->id;

        $response = $this->setUpRequest()->putJson(route('pajuela_toros.update', ['pajuela_toro' => $idPajuelaToroEditar]), $this->pajuela_toro);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'pajuela_toro',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->where('codigo', $this->pajuela_toro['codigo'])
                        ->where('descripcion', $this->pajuela_toro['descripcion'])
                        ->where('fecha', $this->pajuela_toro['fecha'])
                        ->etc()
                )
            );
    }


    public function test_eliminar_pajuela_toro(): void
    {
        $pajuela_toro = $this->generarPajuelasToros();
        $idRandom = random_int(0, $this->cantidad_pajuelaToro - 1);
        $idToDelete = $pajuela_toro[$idRandom]->id;


        $response = $this->setUpRequest()->deleteJson(route('pajuela_toros.destroy', ['pajuela_toro' => $idToDelete]));

        $response->assertStatus(200)->assertJson(['pajuela_toroID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_pajuela_toro(array $pajuela_toro, array $errores): void
    {
        PajuelaToro::factory()->for($this->hacienda)->create(['codigo' => 28472738]);

        $response = $this->setUpRequest()->postJson(route('pajuela_toros.store'), $pajuela_toro);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_autorizacion_maniupular__pajuela_otro_hacienda(): void
    {
        $otroHacienda = Hacienda::factory()
        ->for($this->user)
        ->create(['nombre' => 'otro_hacienda']);

        $pajuela_torolOtroHacienda = PajuelaToro::factory()->for($otroHacienda)->create();

        $idPajuelaOtroHacienda = $pajuela_torolOtroHacienda->id;

        $this->generarPajuelasToros();

        $response = $this->setUpRequest()->putJson(route('pajuela_toros.update', ['pajuela_toro' => $idPajuelaOtroHacienda]), $this->pajuela_toro);

        $response->assertStatus(403);
    }

    public function test_veterinario_no_autorizado_a_crear_pajuela_toro(): void
    {
        $this->cambiarRol($this->user);

        $response = $this->setUpRequest()->postJson(route('pajuela_toros.store'), $this->pajuela_toro);

        $response->assertStatus(403);
    }

    public function test_veterinario_no_autorizado_a_actualizar_pajuela_toro(): void
    {
        $this->cambiarRol($this->user);

        $pajuelasToro = $this->generarPajuelasToros();
        $idRandom = random_int(0, $this->cantidad_pajuelaToro - 1);
        $idPajuelaToroEditar = $pajuelasToro[$idRandom]->id;

        $response = $this->setUpRequest()->putJson(route('pajuela_toros.update', ['pajuela_toro' => $idPajuelaToroEditar]), $this->pajuela_toro);

        $response->assertStatus(403);
    }


    public function test_veterinario_no_autorizado_a_eliminar_pajuela_toro(): void
    {
        $this->cambiarRol($this->user);

        $pajuelasToro = $this->generarPajuelasToros();
        $idRandom = random_int(0, $this->cantidad_pajuelaToro - 1);
        $idPajuelaToroEliminar = $pajuelasToro[$idRandom]->id;

        $response = $this->setUpRequest()->deleteJson(route('pajuela_toros.destroy', ['pajuela_toro' => $idPajuelaToroEliminar]));

        $response->assertStatus(403);
    }
}
