<?php

namespace Tests\Feature;

use App\Models\Comprador;
use App\Models\Estado;
use App\Models\Ganado;
use App\Models\Leche;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class DatosFormulariosTest extends TestCase
{
    use RefreshDatabase;
    private $user;
    private int $cantidad_ganado = 50;
    private $estado;
    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();

        $this->estado = Estado::all();
    }
    private function generarGanado(): Collection
    {
        return Ganado::factory()
            ->count($this->cantidad_ganado)
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->user)
            ->create();
    }
  
    /**
     * A basic feature test example.
     */
  
     public function test_obtener_novillas_que_se_pueden_servir()
     {
        $this->generarGanado();
        $response = $this->actingAs($this->user)->getJson(route('datosParaFormularios.novillasParaMontar'));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->whereType('novillas_para_servicio', 'array')
                ->where('novillas_para_servicio', fn (SupportCollection $novillasParaServir) => count($novillasParaServir) > 1  ? true : false)
                ->has(
                    'novillas_para_servicio.0',
                    fn (AssertableJson $json)
                    => $json->whereAllType([
                        'id' => 'integer',
                        'numero' => 'integer',
                        'peso_actual'=>'string'
                    ])
                )
        );
     }

     public function test_obtener_años_de_ventas_de_ganados()
     {
         Venta::factory()
            ->count(10)
            ->for($this->user)
            ->for(Ganado::factory()->for($this->user)->hasPeso(1)->hasAttached($this->estado)->create())
            ->for(Comprador::factory()->for($this->user)->create())
            ->create();

            $response=$this->actingAs($this->user)->getJson(route('datosParaFormularios.añosVentasGanado'));

            $response->assertStatus(200)->assertJson(
                fn (AssertableJson $json) =>
                $json->whereType('años_ventas_ganado', 'array')
                    ->has(
                        'años_ventas_ganado.0',
                        fn (AssertableJson $json)
                        => $json->whereAllType([
                            'año' => 'integer',
                        ])
                    )
            );
     }
     public function test_obtener_años_de_produccion_de_leches()
     { 
        Leche::factory()
        ->count(10)
        ->for(Ganado::factory()->for($this->user)->hasPeso(1)->hasAttached($this->estado)->create())
        ->for($this->user)
        ->create();

            $response=$this->actingAs($this->user)->getJson(route('datosParaFormularios.añosProduccionLeche'));

            $response->assertStatus(200)->assertJson(
                fn (AssertableJson $json) =>
                $json->whereType('años_produccion_leche', 'array')
                    ->has(
                        'años_produccion_leche.0',
                        fn (AssertableJson $json)
                        => $json->whereAllType([
                            'año' => 'integer',
                        ])
                    )
            );
     }
}
