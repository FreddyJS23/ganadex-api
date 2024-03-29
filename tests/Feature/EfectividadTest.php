<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Ganado;
use App\Models\Parto;
use App\Models\Personal;
use App\Models\Servicio;
use App\Models\Toro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class EfectividadTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $ganado;
    private $toro;
    private $veterinario;
    private $estado;
    private $cantidadServicios;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();
        
        $this->estado = Estado::all();
        
        $this->ganado
            = Ganado::factory()
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->user)
            ->create();
       
            $this->veterinario
        = Personal::factory()
            ->for($this->user)
            ->create(['cargo_id' => 2]);


        $this->toro = Toro::factory()
            ->for($this->user)
            ->for(Ganado::factory()->for($this->user)->create(['sexo' => 'M']))->create();

        $this->cantidadServicios = rand(1, 10);
    }


    /**
     * A basic feature test example.
     */
    public function test_servicios_efectivos_en_la_vaca()
    {
        Servicio::factory()
            ->count($this->cantidadServicios)
            ->for($this->ganado)
            ->for($this->toro)
            ->create(['personal_id' => $this->veterinario]);

        Parto::factory()
            ->count(rand(1, $this->cantidadServicios))
            ->for($this->ganado)
            ->for(Ganado::factory()->for($this->user)->hasAttached(Estado::firstWhere('estado','sano')), 'ganado_cria')
            ->for($this->toro)
            ->create(['personal_id' => $this->veterinario]);

        $response = $this->actingAs($this->user)->getJson(sprintf('api/ganado/%s', $this->ganado->id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->where(
                'efectividad',
                fn ($efectividad) => $efectividad >= 1 && $efectividad <= 100
            )
                ->whereType('efectividad', ['integer', 'double', 'null'])->etc()

        );
    }
    public function test_servicios_efectivos_del_toro()
    {
        Servicio::factory()
            ->count($this->cantidadServicios)
            ->for($this->ganado)
            ->for($this->toro)
            ->create(['personal_id' => $this->veterinario]);

        Parto::factory()
            ->count(rand(1, $this->cantidadServicios))
            ->for($this->ganado)
            ->for(Ganado::factory()->for($this->user)->hasAttached(Estado::firstWhere('estado','sano')), 'ganado_cria')
            ->for($this->toro)
            ->create(['personal_id' => $this->veterinario]);

        $response = $this->actingAs($this->user)->getJson(sprintf('api/toro/%s', $this->toro->id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->where(
                'efectividad',
                fn ($efectividad) => $efectividad >= 1 && $efectividad <= 100
            )
                ->whereType('efectividad', ['integer', 'double', 'null'])->etc()

        );
    }
}
