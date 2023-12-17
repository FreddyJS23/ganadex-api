<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Ganado;
use App\Models\User;
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
}
