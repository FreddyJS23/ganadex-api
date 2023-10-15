<?php

namespace Tests\Feature;

use App\Models\Ganado;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DatosFormulariosTest extends TestCase
{
    use RefreshDatabase;
    private $user;
    private int $cantidad_ganado = 50;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();
    }
    private function generarGanado(): Collection
    {
        return Ganado::factory()
            ->count($this->cantidad_ganado)
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasEstado(1)
            ->for($this->user)
            ->create();
    }
  
    /**
     * A basic feature test example.
     */
  
     public function test_obtener_novillas_que_se_pueden_servir()
     {
        $this->generarGanado();
        $response = $this->actingAs($this->user)->getJson('api/novillas_montar');

        $response->assertStatus(200)->assertJson(['novillas_para_servicio'=>true]);
     }
}
