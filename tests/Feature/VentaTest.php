<?php

namespace Tests\Feature;

use App\Models\Comprador;
use App\Models\Estado;
use App\Models\Ganado;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class VentaTest extends TestCase
{
    use RefreshDatabase;

    private array $venta = [
        'precio' => 350,
    ];

    private int $cantidad_ventas = 10;

    private $user;
    private $estado;

    protected function setUp(): void
    {
        parent::setUp();

        $this->estado = Estado::all();

        $this->user
            = User::factory()->create();
    }

    private function generarVentas(): Collection
    {
        return Venta::factory()
            ->count($this->cantidad_ventas)
            ->for($this->user)
            ->for(Ganado::factory()->for($this->user)->hasPeso(1)->hasAttached($this->estado)->create())
            ->for(Comprador::factory()->for($this->user)->create())
            ->create();
    }
    public static function ErrorInputProvider(): array
    {
        return [

            'caso de insertar datos erróneos' => [
                [
                    'precio' => 'te',
                    'ganado_id' => 'te',
                    'comprador_id' => 'te',

                ], ['precio', 'ganado_id', 'comprador_id']
            ],
            'caso de no insertar datos requeridos' => [
                [], ['precio', 'ganado_id', 'comprador_id']
            ],
            'caso de insertar datos inexistentes' => [
                [
                    'precio' => 400,
                    'ganado_id' => 0,
                    'comprador_id' => 0,

                ], ['ganado_id', 'comprador_id']
            ],
        ];
    }

    /**
     * A basic feature test example.
     */

    public function test_obtener_ventas(): void
    {
        $this->generarVentas();

        $response = $this->actingAs($this->user)->getJson(route('ventas.index'));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'ventas',
                    $this->cantidad_ventas,
                    fn (AssertableJson $json) => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'fecha' => 'string',
                            'peso' => 'integer',
                            'precio' => 'integer|double',
                            'precio_kg' => 'integer|double',
                            'comprador' => 'string',
                        ])->has(
                            'ganado',
                            fn (AssertableJson $json)
                            => $json->whereAllType([
                                'id' => 'integer',
                                'numero' => 'integer',
                            ])
                        )
                )
            );
    }


    public function test_creacion_venta(): void
    {
        $ganado = Ganado::factory()->for($this->user)->hasPeso(1)->hasAttached($this->estado)->create();
        $comprador = Comprador::factory()->for($this->user)->create();
        $this->venta = $this->venta + ['ganado_id' => $ganado->id, 'comprador_id' => $comprador->id];

        $response = $this->actingAs($this->user)->postJson(route('ventas.store'), $this->venta);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'venta',
                    fn (AssertableJson $json) => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'fecha' => 'string',
                            'peso' => 'integer',
                            'precio' => 'integer|double',
                            'precio_kg' => 'integer|double',
                            'comprador' => 'string',
                        ])->has(
                        'ganado',
                        fn (AssertableJson $json)
                        => $json->whereAllType([
                            'id' => 'integer',
                            'numero' => 'integer',
                        ])
                    )
                )
            );
    }


    public function test_obtener_venta(): void
    {
        $venta = $this->generarVentas();
        $idRandom = rand(0, $this->cantidad_ventas - 1);
        $idVenta = $venta[$idRandom]->id;

        $response = $this->actingAs($this->user)->getJson(route('ventas.show', ['venta' => $idVenta]));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'venta',
                    fn (AssertableJson $json) => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'fecha' => 'string',
                            'peso' => 'integer',
                            'precio' => 'integer|double',
                            'precio_kg' => 'integer|double',
                            'comprador' => 'string',
                        ])->has(
                        'ganado',
                        fn (AssertableJson $json)
                        => $json->whereAllType([
                            'id' => 'integer',
                            'numero' => 'integer',
                        ])
                    )
                )
            );
    }


    public function test_actualizar_venta(): void
    {
        $venta = $this->generarVentas();
        $idRandom = rand(0, $this->cantidad_ventas - 1);
        $idVentaEditar = $venta[$idRandom]->id;

        $ganado = Ganado::factory()->for($this->user)->hasAttached($this->estado)->hasPeso(1)->create();
        $comprador = Comprador::factory()->for($this->user)->create();
        $this->venta = $this->venta + ['ganado_id' => $ganado->id, 'comprador_id' => $comprador->id];

        $response = $this->actingAs($this->user)->putJson(route('ventas.update', ['venta' => $idVentaEditar]), $this->venta);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->has(
                'venta',
                fn (AssertableJson $json) =>
                $json->where('precio', $this->venta['precio'])
                ->etc()
            )
        );
    }

    public function test_eliminar_venta(): void
    {
        $venta = $this->generarVentas();
        $idRandom = rand(0, $this->cantidad_ventas - 1);
        $idToDelete = $venta[$idRandom]->id;


        $response = $this->actingAs($this->user)->deleteJson(route('ventas.destroy', ['venta' => $idToDelete]));

        $response->assertStatus(200)->assertJson(['ventaID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_venta($venta, $errores): void
    {

        $response = $this->actingAs($this->user)->postJson(route('ventas.store'), $venta);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_autorizacion_maniupular__venta_otro_usuario(): void
    {
        $ganado = Ganado::factory()->for($this->user)->hasAttached($this->estado)->hasPeso(1)->create();
        $comprador = Comprador::factory()->for($this->user)->create();
        $this->venta = $this->venta + ['ganado_id' => $ganado->id, 'comprador_id' => $comprador->id];

        $otroUsuario = User::factory()->create();

        $ventaOtroUsuario =  Venta::factory()
            ->for($otroUsuario)
            ->for(Ganado::factory()->for($otroUsuario)->hasAttached($this->estado)->hasPeso(1)->create())
            ->for(Comprador::factory()->for($otroUsuario)->create())
            ->create();

        $idVentaOtroUsuario = $ventaOtroUsuario->id;

        $this->generarVentas();


        $response = $this->actingAs($this->user)->putJson(route('ventas.update', ['venta' => $idVentaOtroUsuario]), $this->venta);

        $response->assertStatus(403);
    }
}
