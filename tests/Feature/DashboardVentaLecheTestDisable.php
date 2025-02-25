<?php

namespace Tests\Feature;

use App\Models\Precio;
use App\Models\VentaLeche;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\Common\NeedsFinca;
use Tests\TestCase;

class DashboardVentaLecheTest extends TestCase
{
    use RefreshDatabase;
    use NeedsFinca;

    private int $cantidad_ventaLeche = 100;

    private function generarVentaLeche(): Collection
    {
        return VentaLeche::factory()
            ->count($this->cantidad_ventaLeche)
            ->for(Precio::factory()->for($this->finca))
            ->for($this->finca)
            ->create();
    }

    public function test_obtener_precio_actual(): void
    {
        $this->generarVentaLeche();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardVentaLeche.precioActual'));

        $response->assertStatus(200)->assertJson(fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType(['precio_actual' => 'double']));
    }

    public function test_obtener_variacion_precio_actual_sin_que_haya_precio_anterior(): void
    {
        $this->generarVentaLeche();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardVentaLeche.variacionPrecio'));

        $response->assertStatus(200)->assertJson(fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType(['variacion' => 'integer']));
    }

    public function test_obtener_variacion_precio_actual(): void
    {
        Precio::factory()->for($this->user)->create();

        $this->generarVentaLeche();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardVentaLeche.variacionPrecio'));

        $response->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                $json->whereAllType(['variacion' => 'double'])
            );
    }

    public function test_obtener_ganancias_del_mes(): void
    {
        $this->generarVentaLeche();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardVentaLeche.gananciasDelMes'));

        $response->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                $json->whereAllType(['ganancias' => 'double|integer'])
            );
    }

    public function test_ventas_del_mes(): void
    {
        VentaLeche::factory()
            ->for(Precio::factory()->for($this->user))
            ->for($this->user)
            ->create(['fecha' => now()->format('Y-m-d')]);

        $this->generarVentaLeche();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardVentaLeche.ventasDelMes'));

        $response->assertStatus(200)->assertJson(
            fn(AssertableJSon $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json->whereAllType([
                'ventas_de_leche.0.id' => 'integer',
                'ventas_de_leche.0.fecha' => 'string',
                'ventas_de_leche.0.cantidad' => 'string',
                'ventas_de_leche.0.precio' => 'integer|double',

            ])
        );
    }

    public function test_balance_mensual_venta_leche(): void
    {
        $this->generarVentaLeche();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardVentaLeche.balanceMensual'));

        $response->assertStatus(200)->assertJson(
            fn(AssertableJSon $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json->has('balance_mensual.0', fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType(['fecha' => 'string', 'cantidad' => 'integer|double']))
        );
    }
    public function test_balance_mensual_venta_leche_con_parametro_mes(): void
    {
        VentaLeche::factory()
            ->count($this->cantidad_ventaLeche)
            ->for(Precio::factory()->for($this->user))
            ->for($this->user)
            ->create(['fecha' => now()->addMonth()->format('Y-m-d')]);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardVentaLeche.balanceMensual', ['month' => now()->addMonth()->format('m')]));

        $response->assertStatus(200)->assertJson(
            fn(AssertableJSon $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json->has('balance_mensual.0', fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->where('fecha', now()->addMonth()->format('Y-m-d'))->etc())
        );
    }
}
