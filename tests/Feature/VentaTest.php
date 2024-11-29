<?php

namespace Tests\Feature;

use App\Models\Comprador;
use App\Models\Estado;
use App\Models\Finca;
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
        //'precio' => 350,
    ];

    private int $cantidad_ventas = 10;

    private $user;
    private $estado;
    private $finca;

    protected function setUp(): void
    {
        parent::setUp();

        $this->estado = Estado::all();


        $this->user
            = User::factory()->create();

        $this->user->assignRole('admin');


            $this->finca
            = Finca::factory()
            ->hasAttached($this->user)
            ->create();
    }

    private function generarVentas(): Collection
    {
        return Venta::factory()
            ->count($this->cantidad_ventas)
            ->for($this->finca)
            ->for(Ganado::factory()->for($this->finca)->hasPeso(1)->hasAttached($this->estado)->create())
            ->for(Comprador::factory()->for($this->finca)->create())
            ->create();
    }


    private function cambiarRol(User $user): void
    {
        $user->syncRoles('veterinario');
    }


    public static function ErrorInputProvider(): array
    {
        return [

            'caso de insertar datos erróneos' => [
                [
                   // 'precio' => 'te',
                    'ganado_id' => 'te',
                    'comprador_id' => 'te',

                ], [ 'ganado_id', 'comprador_id']
            ],
            'caso de no insertar datos requeridos' => [
                [], [ 'ganado_id', 'comprador_id']
            ],
            'caso de insertar datos inexistentes' => [
                [
                    //'precio' => 400,
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

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->getJson(route('ventas.index'));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'ventas',
                    $this->cantidad_ventas,
                    fn (AssertableJson $json) => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'fecha' => 'string',
                            'peso' => 'string',
                            //'precio' => 'integer|double',
                            //'precio_kg' => 'integer|double',
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
        $ganado = Ganado::factory()->for($this->finca)->hasPeso(1)->hasAttached($this->estado)->create();
        $comprador = Comprador::factory()->for($this->finca)->create();
        $this->venta = $this->venta + ['ganado_id' => $ganado->id, 'comprador_id' => $comprador->id];

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->postJson(route('ventas.store'), $this->venta);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'venta',
                    fn (AssertableJson $json) => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'fecha' => 'string',
                            'peso' => 'string',
                           /*  'precio' => 'integer|double',
                            'precio_kg' => 'integer|double', */
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

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->getJson(route('ventas.show', ['venta' => $idVenta]));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'venta',
                    fn (AssertableJson $json) => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'fecha' => 'string',
                            'peso' => 'string',
                           /*  'precio' => 'integer|double',
                            'precio_kg' => 'integer|double', */
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

        $ganado = Ganado::factory()->for($this->finca)->hasAttached($this->estado)->hasPeso(1)->create();
        $comprador = Comprador::factory()->for($this->finca)->create();
        $this->venta = $this->venta + ['ganado_id' => $ganado->id, 'comprador_id' => $comprador->id];

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->putJson(route('ventas.update', ['venta' => $idVentaEditar]), $this->venta);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->has(
                'venta',
                fn (AssertableJson $json) =>
                $json->where('ganado.id', $this->venta['ganado_id'])
                ->etc()
            )
        );
    }

    public function test_eliminar_venta(): void
    {
        $venta = $this->generarVentas();
        $idRandom = rand(0, $this->cantidad_ventas - 1);
        $idToDelete = $venta[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->deleteJson(route('ventas.destroy', ['venta' => $idToDelete]));

        $response->assertStatus(200)->assertJson(['ventaID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_venta($venta, $errores): void
    {

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->postJson(route('ventas.store'), $venta);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_autorizacion_maniupular__venta_otro_finca(): void
    {
        $ganado = Ganado::factory()->for($this->finca)->hasAttached($this->estado)->hasPeso(1)->create();
        $comprador = Comprador::factory()->for($this->finca)->create();
        $this->venta = $this->venta + ['ganado_id' => $ganado->id, 'comprador_id' => $comprador->id];

        $otroFinca = Finca::factory()
        ->hasAttached($this->user)
        ->create(['nombre' => 'otro_finca']);

        $ventaOtroFinca =  Venta::factory()
            ->for($otroFinca)
            ->for(Ganado::factory()->for($otroFinca)->hasAttached($this->estado)->hasPeso(1)->create())
            ->for(Comprador::factory()->for($otroFinca)->create())
            ->create();

        $idVentaOtroFinca = $ventaOtroFinca->id;

        $this->generarVentas();


        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->putJson(route('ventas.update', ['venta' => $idVentaOtroFinca]), $this->venta);

        $response->assertStatus(403);
    }

    public function test_veterinario_no_autorizado_a_crear_venta(): void
    {
        $this->cambiarRol($this->user);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->postJson(route('ventas.store'), $this->venta);

        $response->assertStatus(403);
    }

    public function test_veterinario_no_autorizado_a_actualizar_venta(): void
    {
        $this->cambiarRol($this->user);

        $ventas = $this->generarVentas();
        $idRandom = rand(0, $this->cantidad_ventas - 1);
        $idVentaEditar = $ventas[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->putJson(route('ventas.update',['venta'=>$idVentaEditar]), $this->venta);

        $response->assertStatus(403);
    }


    public function test_veterinario_no_autorizado_a_eliminar_venta(): void
    {
        $this->cambiarRol($this->user);


        $ventas = $this->generarVentas();
        $idRandom = rand(0, $this->cantidad_ventas - 1);
        $idVentaEditar = $ventas[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->deleteJson(route('ventas.destroy',['venta'=>$idVentaEditar]));

        $response->assertStatus(403);
    }
}
