<?php

namespace Tests\Feature;

use App\Models\Finca;
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
    }

    private function generarInsumo(): Collection
    {
        return Insumo::factory()
            ->count($this->cantidad_insumo)
            ->for($this->finca)
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

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->getJson('api/insumo');
        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->whereType('insumos', 'array')
                ->has('insumos', $this->cantidad_insumo)
                ->has(
                    'insumos.0',
                    fn (AssertableJson $json)
                    => $json->whereAllType([
                        'id' => 'integer',
                        'insumo' => 'string',
                        'cantidad' => 'integer',
                        'precio' => 'integer|double'
                    ])
                )
        );
    }


    public function test_creacion_insumo(): void
    {

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->postJson('api/insumo', $this->insumo);

        $response->assertStatus(201)->assertJson(
            fn (AssertableJson $json) =>
            $json->has(
                'insumo',
                fn (AssertableJson $json)
                => $json->whereAllType([
                    'id' => 'integer',
                    'insumo' => 'string',
                    'cantidad' => 'integer',
                    'precio' => 'integer|double'
                ])
            )
        );
    }


    public function test_obtener_insumo(): void
    {
        $insumos = $this->generarInsumo();
        $idRandom = rand(0, $this->cantidad_insumo - 1);
        $idInsumo = $insumos[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->getJson(sprintf('api/insumo/%s', $idInsumo));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->has(
                'insumo',
                fn (AssertableJson $json)
                => $json->whereAllType([
                    'id' => 'integer',
                    'insumo' => 'string',
                    'cantidad' => 'integer',
                    'precio' => 'integer|double'
                ])
            )
        );
    }
    public function test_actualizar_insumo(): void
    {
        $insumos = $this->generarInsumo();
        $idRandom = rand(0, $this->cantidad_insumo - 1);
        $idInsumoEditar = $insumos[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->putJson(sprintf('api/insumo/%s', $idInsumoEditar), $this->insumo);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->has(
                'insumo',
                fn (AssertableJson $json) =>
                $json->where('insumo', $this->insumo['insumo'])
                    ->where('cantidad', $this->insumo['cantidad'])
                    ->where('precio', $this->insumo['precio'])
                    ->etc()
            )
        );
    }

    public function test_actualizar_insumo_con_otro_existente_repitiendo_campos_unicos(): void
    {
        $insumoExistente = Insumo::factory()->for($this->finca)->create(['insumo' => 'vacuna']);

        $insumo = $this->generarInsumo();
        $idRandom = rand(0, $this->cantidad_insumo - 1);
        $idInsumoEditar = $insumo[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->putJson(sprintf('api/insumo/%s', $idInsumoEditar), $this->insumo);

        $response->assertStatus(422)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['errors.insumo'])
            ->etc());
    }

    public function test_actualizar_insumo_conservando_campos_unicos(): void
    {
        $insumoExistente = Insumo::factory()->for($this->finca)->create(['insumo' => 'test']);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->putJson(sprintf('api/insumo/%s', $insumoExistente->id), $this->insumo);

        $response->assertStatus(200);
    }

    public function test_eliminar_insumo(): void
    {
        $insumos = $this->generarInsumo();
        $idRandom = rand(0, $this->cantidad_insumo - 1);
        $idToDelete = $insumos[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->deleteJson(sprintf('api/insumo/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['insumoID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_insumo($insumo, $errores): void
    {
        Insumo::factory()->for($this->finca)->create(['insumo' => 'test']);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->postJson('api/insumo', $insumo);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_autorizacion_maniupular__insumo_otro_usuario(): void
    {
        $otroUsuario = User::factory()->create();

        $insumoOtroUsuario = Insumo::factory()->for($otroUsuario)->create();

        $idInsumoOtroUsuario = $insumoOtroUsuario->id;

        $this->generarInsumo();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->putJson(sprintf('api/insumo/%s', $idInsumoOtroUsuario), $this->insumo);

        $response->assertStatus(403);
    }
}
