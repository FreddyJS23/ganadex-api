<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Finca;
use App\Models\Ganado;
use App\Models\Parto;
use App\Models\Personal;
use App\Models\Toro;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class ResumenesAnualesTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $ganadoServicioMonta;
    private $toro;
    private $veterinario;
    private $estado;
    private $finca;

    protected function setUp(): void
    {
        parent::setUp();

        $this->estado = Estado::all();

        $this->user
            = User::factory()->hasConfiguracion()->create();



        $this->finca
            = Finca::factory()
            ->for($this->user)
            ->create();

        $this->ganadoServicioMonta
            = Ganado::factory()
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->finca)
            ->create();

        $this->toro = Toro::factory()
            ->for($this->finca)
            ->for(Ganado::factory()->for($this->finca)->create(['sexo' => 'M']))->create();


        $this->veterinario
            = Personal::factory()
            ->for($this->finca)
            ->create(['cargo_id' => 2]);
    }


    private function generarPartos(): Collection
    {
        Parto::factory()
            ->count(10)
            ->for($this->ganadoServicioMonta)
            ->for(Ganado::factory()->for($this->finca)->hasAttached($this->estado)->create(['fecha_nacimiento' => now()->format('Y-m-d')]), 'ganado_cria')
            ->for($this->toro, 'partoable')
            ->create(['personal_id' => $this->veterinario]);

        Parto::factory()
            ->count(10)
            ->for($this->ganadoServicioMonta)
            ->for(Ganado::factory()->for($this->finca)->hasAttached($this->estado)->create(['fecha_nacimiento' => now()->subYear()->format('Y-m-d')]), 'ganado_cria')
            ->for($this->toro, 'partoable')
            ->create(['personal_id' => $this->veterinario]);

        return Parto::factory()
            ->count(10)
            ->for($this->ganadoServicioMonta)
            ->for(Ganado::factory()->for($this->finca)->hasAttached($this->estado)->create(['fecha_nacimiento' => now()->subYears(2)->format('Y-m-d')]), 'ganado_cria')
            ->for($this->toro, 'partoable')
            ->create(['personal_id' => $this->veterinario]);
    }


    public function test_resumen_natalidad(): void
    {
        $this->generarPartos();
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->getJson(route('resumenesAnual.resumenNatalidad'));
        $response->assertStatus(200);
        $response->assertJson(fn(AssertableJson $json) =>
        $json->has('nacimientos_ultimos_5_año.0', fn(AssertableJson $json)
        => $json->whereAllType([
            'año' => 'string',
            'partos_producidos' => 'integer',
            'poblacion' => 'integer',
            'tasa_natalidad' => 'float|integer'
        ]))
            ->has('nacimientos_año_actual', fn(AssertableJson $json)
            => $json->whereAllType([
                'año' => 'string',
                'total' => 'integer',
                'machos' => 'integer',
                'hembras' => 'integer',
            ])));
    }
}
