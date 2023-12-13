<?php

namespace Tests\Feature;

use App\Models\Comprador;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class CompradorTest extends TestCase
{
    use RefreshDatabase;

    private array $comprador = [
        'nombre' => 'test',
    ];

    private int $cantidad_comprador = 10;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();
    }

    private function generarComprador(): Collection
    {
        return Comprador::factory()
            ->count($this->cantidad_comprador)
            ->for($this->user)
            ->create();
    }
    public static function ErrorInputProvider(): array
    {
        return [
            'caso de que exista el comprador' => [
                [
                    'nombre' => 'test',

                ], ['nombre']
            ],
            'caso de insertar datos erróneos' => [
                [
                    'nombre' => 'te',

                ], ['nombre']
            ],
            'caso de no insertar datos requeridos' => [
                [], ['nombre']
            ],
        ];
    }

    /**
     * A basic feature test example.
     */

    public function test_obtener_compradores(): void
    {
        $this->generarComprador();

        $response = $this->actingAs($this->user)->getJson('api/comprador');
        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'compradores',
                    $this->cantidad_comprador,
                    fn (AssertableJson $json) =>
                    $json->whereAllType(
                        [
                            'id' => 'integer', 'nombre' => 'string'
                        ]
                    )
                )
            );
    }


    public function test_creacion_comprador(): void
    {

        $response = $this->actingAs($this->user)->postJson('api/comprador', $this->comprador);

        $response->assertStatus(201)->assertJson(
            fn (AssertableJson $json) =>
            $json->where('comprador.nombre',$this->comprador['nombre'])
            ->etc()
        );
    }


    public function test_obtener_comprador(): void
    {
        $comprador = $this->generarComprador();
        $idRandom = rand(0, $this->cantidad_comprador - 1);
        $idComprador = $comprador[$idRandom]->id;

        $response = $this->actingAs($this->user)->getJson(sprintf('api/comprador/%s', $idComprador));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->whereAllType(
                [
                    'comprador.id' => 'integer', 'comprador.nombre' => 'string'
                ]
            )
        );
    }
    public function test_actualizar_comprador(): void
    {
        $comprador = $this->generarComprador();
        $idRandom = rand(0, $this->cantidad_comprador - 1);
        $idCompradorEditar = $comprador[$idRandom]->id;

        $response = $this->actingAs($this->user)->putJson(sprintf('api/comprador/%s', $idCompradorEditar), $this->comprador);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->where('comprador.nombre', $this->comprador['nombre'])
            ->etc()
        );
    }

    public function test_actualizar_comprador_con_otro_existente_repitiendo_campos_unicos(): void
    {
        $compradorExistente = Comprador::factory()->for($this->user)->create(['nombre' => 'test']);

        $comprador = $this->generarComprador();
        $idRandom = rand(0, $this->cantidad_comprador - 1);
        $idCompradorEditar = $comprador[$idRandom]->id;

        $response = $this->actingAs($this->user)->putJson(sprintf('api/comprador/%s', $idCompradorEditar), $this->comprador);

        $response->assertStatus(422)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['errors.nombre'])
            ->etc());
    }

    public function test_actualizar_comprador_conservando_campos_unicos(): void
    {
        $compradorExistente = Comprador::factory()->for($this->user)->create(['nombre' => 'test']);

        $response = $this->actingAs($this->user)->putJson(sprintf('api/comprador/%s', $compradorExistente->id), $this->comprador);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->where('comprador.nombre', $this->comprador['nombre'])
            ->etc()
        );
    }

    public function test_eliminar_comprador(): void
    {
        $comprador = $this->generarComprador();
        $idRandom = rand(0, $this->cantidad_comprador - 1);
        $idToDelete = $comprador[$idRandom]->id;


        $response = $this->actingAs($this->user)->deleteJson(sprintf('api/comprador/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['compradorID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_comprador($comprador, $errores): void
    {
        Comprador::factory()->for($this->user)->create(['nombre' => 'test']);

        $response = $this->actingAs($this->user)->postJson('api/comprador', $comprador);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_autorizacion_maniupular__comprador_otro_usuario(): void
    {
        $otroUsuario = User::factory()->create();

        $compradorOtroUsuario = Comprador::factory()->for($otroUsuario)->create();

        $idCompradorOtroUsuario = $compradorOtroUsuario->id;

        $this->generarComprador();

        $response = $this->actingAs($this->user)->putJson(sprintf('api/comprador/%s', $idCompradorOtroUsuario), $this->comprador);

        $response->assertStatus(403);
    }
}
