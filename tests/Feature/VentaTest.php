<?php

namespace Tests\Feature;

use App\Models\Comprador;
use App\Models\Estado;
use App\Models\Hacienda;
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
        'fecha' => '2020-10-02',

    ];

    private int $cantidad_ventas = 10;

    private $user;
    private $estado;
    private $hacienda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->estado = Estado::all();


        $this->user
            = User::factory()->hasConfiguracion()->create();

        $this->user->assignRole('admin');


            $this->hacienda
            = Hacienda::factory()
            ->for($this->user)
            ->create();
    }

    private function generarVentas(): Collection
    {
        return Venta::factory()
            ->count($this->cantidad_ventas)
            ->for($this->hacienda)
            ->for(Ganado::factory()->for($this->hacienda)->hasPeso(1)->hasAttached($this->estado)->create())
            ->for(Comprador::factory()->for($this->hacienda)->create())
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

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('ventas.index'));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'ventas',
                    $this->cantidad_ventas,
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'fecha' => 'string',
                            'peso' => 'string',
                            //'precio' => 'integer|double',
                            //'precio_kg' => 'integer|double',
                            'comprador' => 'string',
                        ])->has(
                            'ganado',
                            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                            => $json->whereAllType([
                                'id' => 'integer',
                                'numero' => 'integer|null',
                            ])
                        )
                )
            );
    }


    public function test_creacion_venta(): void
    {
        $ganado = Ganado::factory()->for($this->hacienda)->hasPeso(1)->hasAttached($this->estado)->create();
        $comprador = Comprador::factory()->for($this->hacienda)->create();
        $this->venta = $this->venta + ['ganado_id' => $ganado->id, 'comprador_id' => $comprador->id];

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(route('ventas.store'), $this->venta);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'venta',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'fecha' => 'string',
                            'peso' => 'string',
                           /*  'precio' => 'integer|double',
                            'precio_kg' => 'integer|double', */
                            'comprador' => 'string',
                        ])->has(
                            'ganado',
                            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                            => $json->whereAllType([
                                'id' => 'integer',
                                'numero' => 'integer|null',
                            ])
                        )
                )
            );
    }

    public function test_creacion_venta_lote(): void
    {
        $ganados = Ganado::factory()->count(3)->for($this->hacienda)->hasPeso(1)->hasAttached($this->estado)->create();
        $comprador = Comprador::factory()->for($this->hacienda)->create();

        $data = [
            'fecha' => '2025-04-20',
            'ganado_ids' => $ganados->pluck('id')->toArray(),
            'comprador_id' => $comprador->id,
        ];

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id])->postJson(route('ventas.storeBatch'), $data);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json) => $json->has('ventas', 3)
            );
    }

    public function test_obtener_venta(): void
    {
        $venta = $this->generarVentas();
        $idRandom = random_int(0, $this->cantidad_ventas - 1);
        $idVenta = $venta[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('ventas.show', ['venta' => $idVenta]));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'venta',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'fecha' => 'string',
                            'peso' => 'string',
                           /*  'precio' => 'integer|double',
                            'precio_kg' => 'integer|double', */
                            'comprador' => 'string',
                        ])->has(
                            'ganado',
                            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                            => $json->whereAllType([
                                'id' => 'integer',
                                'numero' => 'integer|null',
                            ])
                        )
                )
            );
    }


    public function test_actualizar_venta(): void
    {
        $venta = $this->generarVentas();
        $idRandom = random_int(0, $this->cantidad_ventas - 1);
        $idVentaEditar = $venta[$idRandom]->id;

        $ganado = Ganado::factory()->for($this->hacienda)->hasAttached($this->estado)->hasPeso(1)->create();
        $comprador = Comprador::factory()->for($this->hacienda)->create();
        $this->venta = $this->venta + ['ganado_id' => $ganado->id, 'comprador_id' => $comprador->id];

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(route('ventas.update', ['venta' => $idVentaEditar]), $this->venta);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json->has(
                'venta',
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                $json->where('ganado.id', $this->venta['ganado_id'])
                ->etc()
            )
        );
    }

    public function test_eliminar_venta(): void
    {
        $venta = $this->generarVentas();
        $idRandom = random_int(0, $this->cantidad_ventas - 1);
        $idToDelete = $venta[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->deleteJson(route('ventas.destroy', ['venta' => $idToDelete]));

        $response->assertStatus(200)->assertJson(['ventaID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_venta(array $venta, array $errores): void
    {

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(route('ventas.store'), $venta);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_autorizacion_maniupular__venta_otro_hacienda(): void
    {
        $ganado = Ganado::factory()->for($this->hacienda)->hasAttached($this->estado)->hasPeso(1)->create();
        $comprador = Comprador::factory()->for($this->hacienda)->create();
        $this->venta = $this->venta + ['ganado_id' => $ganado->id, 'comprador_id' => $comprador->id];

        $otroHacienda = Hacienda::factory()
        ->for($this->user)
        ->create(['nombre' => 'otro_hacienda']);

        $ventaOtroHacienda =  Venta::factory()
            ->for($otroHacienda)
            ->for(Ganado::factory()->for($otroHacienda)->hasAttached($this->estado)->hasPeso(1)->create())
            ->for(Comprador::factory()->for($otroHacienda)->create())
            ->create();

        $idVentaOtroHacienda = $ventaOtroHacienda->id;

        $this->generarVentas();


        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(route('ventas.update', ['venta' => $idVentaOtroHacienda]), $this->venta);

        $response->assertStatus(403);
    }

    public function test_veterinario_no_autorizado_a_crear_venta(): void
    {
        $this->cambiarRol($this->user);

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(route('ventas.store'), $this->venta);

        $response->assertStatus(403);
    }

    public function test_veterinario_no_autorizado_a_actualizar_venta(): void
    {
        $this->cambiarRol($this->user);

        $ventas = $this->generarVentas();
        $idRandom = random_int(0, $this->cantidad_ventas - 1);
        $idVentaEditar = $ventas[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(route('ventas.update', ['venta' => $idVentaEditar]), $this->venta);

        $response->assertStatus(403);
    }


    public function test_veterinario_no_autorizado_a_eliminar_venta(): void
    {
        $this->cambiarRol($this->user);


        $ventas = $this->generarVentas();
        $idRandom = random_int(0, $this->cantidad_ventas - 1);
        $idVentaEditar = $ventas[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->deleteJson(route('ventas.destroy', ['venta' => $idVentaEditar]));

        $response->assertStatus(403);
    }
}
