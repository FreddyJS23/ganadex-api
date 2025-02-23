<?php

namespace Tests\Feature;

use App\Models\Comprador;
use App\Models\Estado;
use App\Models\Finca;
use App\Models\Ganado;
use App\Models\User;
use App\Models\Venta;
use Doctrine\DBAL\Schema\Sequence;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class DashboardVentaGanadoTest extends TestCase
{
    use RefreshDatabase;

    private $precios;
    private $estado;
    private int $cantidad_ventas = 10;
    private $finca;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->hasConfiguracion()->create();

        $this->estado = Estado::all();

        $this->finca
            = Finca::factory()
            ->for($this->user)
            ->create();
    }

    private function generarVentas(): Collection
    {
        $compradores = Comprador::factory()->for($this->finca)->count(5)->create();

        return Venta::factory()
            ->count($this->cantidad_ventas)
            ->for($this->finca)
            ->for(Ganado::factory()->for($this->finca)->hasPeso(1)->hasAttached($this->estado)->create())
            ->sequence(
                ['comprador_id' => $compradores->random()->id],
                ['comprador_id' => $compradores->random()->id],
                ['comprador_id' => $compradores->random()->id],
            )
            ->create();
    }


    /**
     * A basic feature test example.
     */
    public function test_obtener_mejor_comprador(): void
    {
        $this->generarVentas();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardVentaGanado.mejorComprador'));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJSon $json) =>
            $json->whereAllType([
                'comprador.id' => 'integer',
                'comprador.nombre' => 'string',
            ])
        );
    }

    public function test_error_no_haya_compradores_registrados_para_obtener_mejor_comprador(): void
    {

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardVentaGanado.mejorComprador'));

        $response->assertJson(['comprador' => null]);
    }

   /*  public function test_obtener_mejor_venta(): void
    {
        $this->generarVentas();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardVentaGanado.mejorVenta'));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJSon $json) =>
            $json->whereAllType([
                'venta.id' => 'integer',
                'venta.fecha' => 'string',
                'venta.peso' => 'string',
                'venta.precio' => 'integer|double',
                'venta.precio_kg' => 'integer|double',
                'venta.comprador' => 'string',
            ])->has(
                'venta.ganado',
                fn (AssertableJson $json)
                => $json->whereAllType([
                    'id' => 'integer',
                    'numero' => 'integer',
                ])
            )
        );
    } */

   /*  public function test_error_no_haya_ventas_registrados_para_obtener_mejor_venta(): void
    {
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardVentaGanado.mejorVenta'));

        $response->assertJson(['venta' => null]);
    }

    public function test_obtener_peor_venta(): void
    {
        $this->generarVentas();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardVentaGanado.peorVenta'));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJSon $json) =>
            $json->whereAllType([
                'venta.id' => 'integer',
                'venta.fecha' => 'string',
                'venta.peso' => 'string',
                'venta.precio' => 'integer|double',
                'venta.precio_kg' => 'integer|double',
                'venta.comprador' => 'string',
            ])->has(
                'venta.ganado',
                fn (AssertableJson $json)
                => $json->whereAllType([
                    'id' => 'integer',
                    'numero' => 'integer',
                ])
            )
        );
    } */


    public function test_ventas_del_mes(): void
    {
        Venta::factory()
            ->count($this->cantidad_ventas)
            ->for($this->finca)
            ->for(Ganado::factory()->for($this->finca)->hasPeso(1)->hasAttached($this->estado)->create())
            ->for(Comprador::factory()->for($this->finca)->create())
            ->create(['fecha' => now()->format('Y-m-d')]);

        $this->generarVentas();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardVentaGanado.ventasDelMes'));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->whereType('ventas', 'array')
                ->has(
                    'ventas.0',
                    fn (AssertableJson $json)
                    => $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'peso' => 'string',
                        /* 'precio' => 'integer|double',
                        'precio_kg' => 'integer|double', */
                        'comprador' => 'string',
                    ]) ->has(
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

    public function test_obtener_balance_anual_ventas(): void
    {
        $this->generarVentas();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardVentaGanado.balanceAnualVentas'));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJSon $json) =>
            $json->has('balance_anual', 12, fn (AssertableJson $json) => $json->whereAllType(['mes' => 'string', 'ventas' => 'integer']))
        );
    }
    public function test_obtener_balance_anual_ventas__con_parametro_año(): void
    {
        $this->generarVentas();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardVentaGanado.balanceAnualVentas', ['year' => 2022,]));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJSon $json) =>
            $json->has(
                'balance_anual',
                12,
                fn (AssertableJson $json)
                => $json->whereAllType(['mes' => 'string', 'ventas' => 'integer'])
                ->where('ventas', 0)
            )
        );
    }
}
