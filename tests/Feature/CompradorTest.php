<?php

namespace Tests\Feature;

use App\Models\Comprador;
use App\Models\Hacienda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\Common\NeedsComprador;
use Tests\TestCase;

class CompradorTest extends TestCase
{
    use RefreshDatabase;
    use NeedsComprador;

    public static function ErrorInputProvider(): array
    {
        return [
            'caso de que exista el comprador' => [
                [
                    'nombre' => 'test',
                ],
                ['nombre']
            ],
            'caso de insertar datos erróneos' => [
                [
                    'nombre' => 'te',
                ],
                ['nombre']
            ],
            'caso de no insertar datos requeridos' => [
                [],
                ['nombre']
            ],
        ];
    }

    private function setUpRequest(): static
    {
        $this
            ->actingAs($this->user)
            ->withSession($this->getSessionInitializationArray());

        return $this;
    }

    public function test_obtener_compradores(): void
    {
        $this->generarComprador();

        $this
            ->setUpRequest()
            ->getJson('api/comprador')
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): AssertableJson => $json->has(
                    key: 'compradores',
                    length: $this->cantidad_comprador,
                    callback: fn(AssertableJson $json): AssertableJson => $json->whereAllType(
                        [
                            'id' => 'integer',
                            'nombre' => 'string'
                        ]
                    )
                )
            );
    }


    public function test_creacion_comprador(): void
    {
        $this
            ->setUpRequest()
            ->postJson('api/comprador', $this->comprador)
            ->assertStatus(201)
            ->assertJson(
                fn(AssertableJson $json): AssertableJson => $json
                    ->where(
                        key: 'comprador.nombre',
                        expected: $this->comprador['nombre']
                    )
                    ->etc()
            );
    }


    public function test_obtener_comprador(): void
    {
        $comprador = $this->generarComprador();
        $idRandom = random_int(0, $this->cantidad_comprador - 1);
        $idComprador = $comprador[$idRandom]->id;

        $this
            ->setUpRequest()
            ->getJson(sprintf('api/comprador/%s', $idComprador))
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json): AssertableJson => $json->whereAllType(
                [
                    'comprador.id' => 'integer',
                    'comprador.nombre' => 'string'
                ]
            ));
    }

    public function test_actualizar_comprador(): void
    {
        $comprador = $this->generarComprador();
        $idRandom = random_int(0, $this->cantidad_comprador - 1);
        $idCompradorEditar = $comprador[$idRandom]->id;

        $this
            ->setUpRequest()
            ->putJson(sprintf('api/comprador/%s', $idCompradorEditar), $this->comprador)
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): AssertableJson => $json
                    ->where('comprador.nombre', $this->comprador['nombre'])
                    ->etc()
            );
    }

    public function test_actualizar_comprador_con_otro_existente_repitiendo_campos_unicos(): void
    {
        Comprador::factory()->for($this->hacienda)->create(['nombre' => 'test']);

        $comprador = $this->generarComprador();
        $idRandom = random_int(0, $this->cantidad_comprador - 1);
        $idCompradorEditar = $comprador[$idRandom]->id;

        $this
            ->setUpRequest()
            ->putJson("api/comprador/$idCompradorEditar", $this->comprador)
            ->assertStatus(422)
            ->assertJson(
                fn(AssertableJson $json): AssertableJson => $json
                    ->hasAll(['errors.nombre'])
                    ->etc()
            );
    }

    public function test_actualizar_comprador_conservando_campos_unicos(): void
    {
        $compradorExistente = Comprador::factory()
            ->for($this->hacienda)
            ->create(['nombre' => 'test']);

        $this
            ->setUpRequest()
            ->putJson(
                uri: sprintf('api/comprador/%s', $compradorExistente->id),
                data: $this->comprador
            )
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): AssertableJson => $json
                    ->where('comprador.nombre', $this->comprador['nombre'])
                    ->etc()
            );
    }

    public function test_eliminar_comprador(): void
    {
        $comprador = $this->generarComprador();
        $idRandom = random_int(0, $this->cantidad_comprador - 1);
        $idToDelete = $comprador[$idRandom]->id;

        $this
            ->setUpRequest()
            ->deleteJson(sprintf('api/comprador/%s', $idToDelete))
            ->assertStatus(200)
            ->assertJson(['compradorID' => $idToDelete]);
    }

    /** @dataProvider ErrorinputProvider */
    public function test_error_validacion_registro_comprador(array $comprador, array $errores): void
    {
        Comprador::factory()->for($this->hacienda)->create(['nombre' => 'test']);

        $this
            ->setUpRequest()
            ->postJson('api/comprador', $comprador)
            ->assertStatus(422)
            ->assertInvalid($errores);
    }

    public function test_autorizacion_maniupular__comprador_otro_usuario(): void
    {
        $otroHacienda = Hacienda::factory()
            ->for($this->user)
            ->create(['nombre' => 'otro_hacienda']);

        $compradorOtroUsuario = Comprador::factory()->for($otroHacienda)->create();
        $idCompradorOtroUsuario = $compradorOtroUsuario->id;

        $this->generarComprador();

        $this
            ->cambiarRol($this->user)
            ->setUpRequest()
            ->putJson(
                uri: sprintf('api/comprador/%s', $idCompradorOtroUsuario),
                data: $this->comprador
            )
            ->assertStatus(403);
    }


    public function test_veterinario_no_autorizado_a_crear_comprador(): void
    {
        $this
            ->cambiarRol($this->user)
            ->setUpRequest()
            ->postJson('api/comprador', $this->comprador)
            ->assertStatus(403);
    }

    public function test_veterinario_no_autorizado_a_actualizar_comprador(): void
    {
        $compradores = $this->generarComprador();
        $idRandom = random_int(0, $this->cantidad_comprador - 1);
        $idCompradorEditar = $compradores[$idRandom]->id;

        $this
            ->cambiarRol($this->user)
            ->setUpRequest()
            ->putJson(
                uri: sprintf('api/comprador/%s', $idCompradorEditar),
                data: $this->comprador
            )
            ->assertStatus(403);
    }


    public function test_veterinario_no_autorizado_a_eliminar_comprador(): void
    {
        $compradores = $this->generarComprador();
        $idRandom = random_int(0, $this->cantidad_comprador - 1);
        $idToDelete = $compradores[$idRandom]->id;

        $this
            ->cambiarRol($this->user)
            ->setUpRequest()
            ->deleteJson(sprintf('api/comprador/%s', $idToDelete))
            ->assertStatus(403);
    }
}
