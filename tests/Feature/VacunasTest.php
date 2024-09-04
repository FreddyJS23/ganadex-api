<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vacuna;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class VacunasTest extends TestCase
{
    use RefreshDatabase;

    private array $vacuna = [
        'nombre' => 'vacuna',
        'tipo_animal' => ['rebano', 'becerro'],
        'intervalo_dosis' => 33,
    ];

    private int $cantidad_vacunas = 10;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();
    }

    private function generarVacunas(): Collection
    {
        return Vacuna::factory()
            ->count($this->cantidad_vacunas)
            ->create();
    }
    public static function ErrorInputProvider(): array
    {
        return [
          
            'caso de insertar datos erróneos' => [
                [
                    'nombre' => 'te',
                    'tipo_animal' => 'uuuu',
                    'intervalo_dosis' => 'd32',
                ],
                ['nombre', 'tipo_animal', 'intervalo_dosis']
            ],
            'caso de no insertar datos requeridos' => [
                [],
                ['nombre', 'tipo_animal', 'intervalo_dosis']
            ],
        ];
    }



    /**
     * A basic feature test example.
     */

    public function test_obtener_vacunas(): void
    {
        $this->generarVacunas();

        $response = $this->actingAs($this->user)->getJson(route('vacunas.index'));
        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json) =>
            $json->whereType('vacunas', 'array')
                ->has('vacunas', $this->cantidad_vacunas)
                ->has(
                    'vacunas.0',
                    fn(AssertableJson $json)
                    => $json->whereAllType([
                        'id' => 'integer',
                    'nombre' => 'string',
                    'tipo_animal' => 'array',
                    'intervalo_dosis' => 'integer',
                    ])
                )
        );
    }


    public function test_creacion_vacuna(): void
    {

        $response = $this->actingAs($this->user)->postJson(route('vacunas.store'), $this->vacuna);

        $response->assertStatus(201)->assertJson(
            fn(AssertableJson $json) =>
            $json->has(
                'vacuna',
                fn(AssertableJson $json)
                => $json->whereAllType([
                    'id' => 'integer',
                    'nombre' => 'string',
                    'tipo_animal' => 'array',
                    'intervalo_dosis' => 'integer',
                ])
            )
        );
    }


    public function test_obtener_vacuna(): void
    {
        $vacunas = $this->generarVacunas();
        $idRandom = rand(0, $this->cantidad_vacunas - 1);
        $idVacuna = $vacunas[$idRandom]->id;

        $response = $this->actingAs($this->user)->getJson(sprintf('api/vacunas/%s', $idVacuna));

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json) =>
            $json->has(
                'vacuna',
                fn(AssertableJson $json)
                => $json->whereAllType([
                    'id' => 'integer',
                    'nombre' => 'string',
                    'tipo_animal' => 'array',
                    'intervalo_dosis' => 'integer',
                ])
            )
        );
    }

    public function test_actualizar_vacuna(): void
    {
        $vacunas = $this->generarVacunas();
        $idRandom = rand(0, $this->cantidad_vacunas - 1);
        $idVacunaEditar = $vacunas[$idRandom]->id;

        $response = $this->actingAs($this->user)->putJson(sprintf('api/vacunas/%s', $idVacunaEditar), $this->vacuna);

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json) =>
            $json->has(
                'vacuna',
                fn(AssertableJson $json) =>
                $json->where('nombre', $this->vacuna['nombre'])
                    ->where('tipo_animal', $this->vacuna['tipo_animal'])
                    ->where('intervalo_dosis', $this->vacuna['intervalo_dosis'])
                    ->etc()
            )
        );
    }

 
    public function test_eliminar_vacuna(): void
    {
        $vacunas = $this->generarVacunas();
        $idRandom = rand(0, $this->cantidad_vacunas - 1);
        $idToDelete = $vacunas[$idRandom]->id;


        $response = $this->actingAs($this->user)->deleteJson(sprintf('api/vacunas/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['vacunaID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_vacuna($vacuna, $errores): void
    {

        $response = $this->actingAs($this->user)->postJson('api/vacunas', $vacuna);

        $response->assertStatus(422)->assertInvalid($errores);
    }

}
