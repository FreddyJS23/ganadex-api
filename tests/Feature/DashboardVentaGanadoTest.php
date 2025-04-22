<?php

namespace Tests\Feature;

use App\Models\Comprador;
use App\Models\Ganado;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\Common\NeedsEstado;
use Tests\Feature\Common\NeedsHacienda;
use Tests\TestCase;

class DashboardVentaGanadoTest extends TestCase
{
    use RefreshDatabase;

    use NeedsHacienda {
        NeedsHacienda::setUp as needsHaciendaSetUp;
    }

    use NeedsEstado {
        NeedsEstado::setUp as needsEstadoSetUp;
    }

    private int $cantidad_ventas = 10;

    protected function setUp(): void
    {
        $this->needsHaciendaSetUp();
        $this->needsEstadoSetUp();
    }

    private function generarVentas(): Collection
    {
        $compradores = Comprador::factory()
            ->for($this->hacienda)
            ->count(5)
            ->create();

        return Venta::factory()
            ->count($this->cantidad_ventas)
            ->for($this->hacienda)
            ->for(Ganado::factory()
                ->for($this->hacienda)
                ->hasPeso(1)
                ->hasAttached($this->estado)->create())
            ->sequence(
                ['comprador_id' => $compradores->random()->id],
                ['comprador_id' => $compradores->random()->id],
                ['comprador_id' => $compradores->random()->id],
            )
            ->create();
    }

    private function setUpRequest(): static
    {
        $this
            ->actingAs($this->user)
            ->withSession($this->getSessionInitializationArray());

        return $this;
    }

    public function test_obtener_mejor_comprador(): void
    {
        $this->generarVentas();

        $this
            ->setUpRequest()
            ->getJson(route('dashboardVentaGanado.mejorComprador'))
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJSon $json): AssertableJson =>
                $json->whereAllType([
                    'comprador.id' => 'integer',
                    'comprador.nombre' => 'string',
                ])
            );
    }

    public function test_error_no_haya_compradores_registrados_para_obtener_mejor_comprador(): void
    {
        $this
            ->setUpRequest()
            ->getJson(route('dashboardVentaGanado.mejorComprador'))
            ->assertJson(['comprador' => null]);
    }

    /*public function test_obtener_mejor_venta(): void
    {
        $this->generarVentas();

        $this
            ->setUpRequest()
            ->getJson(route('dashboardVentaGanado.mejorVenta'))
            ->assertStatus(200)->assertJson(
                fn(AssertableJSon $json) => $json
                    ->whereAllType([
                        'venta.id' => 'integer',
                        'venta.fecha' => 'string',
                        'venta.peso' => 'string',
                        'venta.precio' => 'integer|double',
                        'venta.precio_kg' => 'integer|double',
                        'venta.comprador' => 'string',
                    ])->has(
                        'venta.ganado',
                        fn(AssertableJson $json) => $json->whereAllType([
                            'id' => 'integer',
                            'numero' => 'integer',
                        ])
                    )
            );
    }*/

    /*public function test_error_no_haya_ventas_registrados_para_obtener_mejor_venta(): void
    {
        $this
            ->setUpRequest()
            ->getJson(route('dashboardVentaGanado.mejorVenta'))
            ->assertJson(['venta' => null]);
    }*/

    /*public function test_obtener_peor_venta(): void
    {
        $this->generarVentas();

        $this
            ->setUpRequest()
            ->getJson(route('dashboardVentaGanado.peorVenta'))
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJSon $json) => $json
                    ->whereAllType([
                        'venta.id' => 'integer',
                        'venta.fecha' => 'string',
                        'venta.peso' => 'string',
                        'venta.precio' => 'integer|double',
                        'venta.precio_kg' => 'integer|double',
                        'venta.comprador' => 'string',
                    ])->has(
                        'venta.ganado',
                        fn(AssertableJson $json) => $json->whereAllType([
                            'id' => 'integer',
                            'numero' => 'integer',
                        ])
                    )
            );
    }*/

    public function test_ventas_del_mes(): void
    {
        Venta::factory()
            ->count($this->cantidad_ventas)
            ->for($this->hacienda)
            ->for(Ganado::factory()->for($this->hacienda)->hasPeso(1)->hasAttached($this->estado)->create())
            ->for(Comprador::factory()->for($this->hacienda)->create())
            ->create(['fecha' => now()->format('Y-m-d')]);

        $this->generarVentas();

        $this
            ->setUpRequest()
            ->getJson(route('dashboardVentaGanado.ventasDelMes'))
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): AssertableJson => $json
                    ->whereType('ventas', 'array')
                    ->has(
                        'ventas.0',
                        fn(AssertableJson $json): AssertableJson => $json
                            ->whereAllType([
                                'id' => 'integer',
                                'fecha' => 'string',
                                'peso' => 'string',
                                /* 'precio' => 'integer|double',
                                'precio_kg' => 'integer|double', */
                                'comprador' => 'string',
                            ])
                            ->has(
                                'ganado',
                                fn(AssertableJson $json): AssertableJson => $json->whereAllType([
                                    'id' => 'integer',
                                    'numero' => 'integer|null',
                                ])
                            )
                    )
            );
    }

    public function test_obtener_balance_anual_ventas(): void
    {
        $this->generarVentas();

        $this
            ->setUpRequest()
            ->getJson(route('dashboardVentaGanado.balanceAnualVentas'))
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJSon $json): AssertableJson => $json
                    ->has(
                        'balance_anual',
                        12,
                        fn(AssertableJson $json): AssertableJson => $json->whereAllType([
                            'mes' => 'string',
                            'ventas' => 'integer'
                        ])
                    )
            );
    }

    public function test_obtener_balance_anual_ventas__con_parametro_año(): void
    {
        $this->generarVentas();

        $this
            ->setUpRequest()
            ->getJson(route('dashboardVentaGanado.balanceAnualVentas', ['year' => 2022]))
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJSon $json): AssertableJson => $json->has(
                    'balance_anual',
                    12,
                    fn(AssertableJson $json): AssertableJson => $json
                        ->whereAllType([
                            'mes' => 'string',
                            'ventas' => 'integer'
                        ])
                        ->where('ventas', 0)
                )
            );
    }
}
