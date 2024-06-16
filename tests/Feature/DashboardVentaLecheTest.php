<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Precio;
use App\Models\User;
use App\Models\VentaLeche;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class DashboardVentaLecheTest extends TestCase
{

   use RefreshDatabase;
   
   private $precios;

    private int $cantidad_ventaLeche = 10;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();
    }

    private function generarVentaLeche(): Collection
    {
        return VentaLeche::factory()
            ->count($this->cantidad_ventaLeche)
            ->for(Precio::factory()->for($this->user))
            ->for($this->user)
            ->create();
    }


    /**
     * A basic feature test example.
     */
    public function test_obtener_precio_actual(): void
    {
        $this->generarVentaLeche();

        $response = $this->actingAs($this->user)->getJson(route('dashboardVentaLeche.precioActual'));

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) => $json->whereAllType(['precio_actual' => 'double']));
    }

    public function test_obtener_variacion_precio_actual_sin_que_haya_precio_anterior(): void
    {
        $this->generarVentaLeche();

        $response = $this->actingAs($this->user)->getJson(route('dashboardVentaLeche.variacionPrecio'));

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) => $json->whereAllType(['variacion' => 'integer']));
    }

    public function test_obtener_variacion_precio_actual(): void
    {
        Precio::factory()->for($this->user)->create();

        $this->generarVentaLeche();

        $response = $this->actingAs($this->user)->getJson(route('dashboardVentaLeche.variacionPrecio'));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->whereAllType(['variacion' => 'double'])
            );
    }

    public function test_obtener_ganancias_del_mes(): void
    {
        $this->generarVentaLeche();

        $response = $this->actingAs($this->user)->getJson(route('dashboardVentaLeche.gananciasDelMes'));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->whereAllType(['ganancias' => 'double|integer'])
            );
    }

    public function test_ventas_del_mes(): void
    {
        VentaLeche::factory()
            ->for(Precio::factory()->for($this->user))
            ->for($this->user)
            ->create(['fecha'=>now()->format('Y-m-d')]);
       
        $this->generarVentaLeche();

        $response = $this->actingAs($this->user)->getJson(route('dashboardVentaLeche.ventasDelMes'));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJSon $json) =>
            $json->whereAllType([
                'ventas_de_leche.0.id' => 'integer',
                'ventas_de_leche.0.fecha' => 'string',
                'ventas_de_leche.0.cantidad' => 'string',
                'ventas_de_leche.0.precio' => 'integer|double',
               
            ])
        );
    }
}
