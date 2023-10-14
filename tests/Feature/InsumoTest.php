<?php

namespace Tests\Feature;

use App\Models\Insumo;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class InsumoTest extends TestCase
{
    use RefreshDatabase;

    private array $insumo = [
        'insumo' => 'vacuna',
        'cantidad' => 50,
        'precio' => 33,
    ];

    private int $cantidad_insumo = 10;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();
    }

    private function generarInsumo(): Collection
    {
        return Insumo::factory()
            ->count($this->cantidad_insumo)
            ->for($this->user)
            ->create();
    }
    public static function ErrorInputProvider(): array
    {
        return [
            'caso de que exista el insumo' => [
                [
                    'insumo' => 'test',
                    'cantidad' => 30,
                    'precio' => 10,

                ], ['insumo']
            ],
            'caso de insertar datos erróneos' => [
                [
                    'insumo' => 'te',
                    'cantidad' => 1000,
                    'precio' => 'd32',
                ], ['insumo', 'cantidad', 'precio']
            ],
            'caso de no insertar datos requeridos' => [
                [], ['insumo', 'cantidad', 'precio']
            ],
        ];
    }



    /**
     * A basic feature test example.
     */

    public function test_obtener_insumos(): void
    {
        $this->generarInsumo();

        $response = $this->actingAs($this->user)->getJson('api/insumo');
        $response->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->has('insumos', $this->cantidad_insumo));
    }


    public function test_creacion_insumo(): void
    {

        $response = $this->actingAs($this->user)->postJson('api/insumo', $this->insumo);

        $response->assertStatus(201)->assertJson(['insumo' => true]);
    }


    public function test_obtener_insumo(): void
    {
        $insumos = $this->generarInsumo();
        $idRandom = rand(0, $this->cantidad_insumo - 1);
        $idInsumo = $insumos[$idRandom]->id;

        $response = $this->actingAs($this->user)->getJson(sprintf('api/insumo/%s', $idInsumo));

        $response->assertStatus(200)->assertJson(['insumo' => true]);
    }
    public function test_actualizar_insumo(): void
    {
        $insumos = $this->generarInsumo();
        $idRandom = rand(0, $this->cantidad_insumo - 1);
        $idInsumoEditar = $insumos[$idRandom]->id;

        $response = $this->actingAs($this->user)->putJson(sprintf('api/insumo/%s', $idInsumoEditar), $this->insumo);

        $response->assertStatus(200)->assertJson(['insumo' => true]);
    }

    public function test_eliminar_insumo(): void
    {
        $insumos = $this->generarInsumo();
        $idRandom = rand(0, $this->cantidad_insumo - 1);
        $idToDelete = $insumos[$idRandom]->id;


        $response = $this->actingAs($this->user)->deleteJson(sprintf('api/insumo/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['insumoID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_insumo($insumo, $errores): void
    {
        Insumo::factory()->for($this->user)->create(['insumo' => 'test']);

        $response = $this->actingAs($this->user)->postJson('api/insumo', $insumo);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_autorizacion_maniupular__insumo_otro_usuario(): void
    {
        $otroUsuario = User::factory()->create();

        $insumoOtroUsuario = Insumo::factory()->for($otroUsuario)->create();

        $idInsumoOtroUsuario = $insumoOtroUsuario->id;

        $this->generarInsumo();

        $response = $this->actingAs($this->user)->putJson(sprintf('api/insumo/%s', $idInsumoOtroUsuario), $this->insumo);

        $response->assertStatus(403);
    }
}
