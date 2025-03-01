<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Finca;
use App\Models\Ganado;
use App\Models\Parto;
use App\Models\Personal;
use App\Models\Servicio;
use App\Models\Toro;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    private int $cantidadServicios;
    private $finca;

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

        $this->ganado
            = Ganado::factory()
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->finca)
            ->create();

        $this->veterinario
            = Personal::factory()
            ->for($this->finca)
            ->create(['cargo_id' => 2]);


        $this->toro = Toro::factory()
            ->for($this->finca)
            ->for(Ganado::factory()->for($this->finca)->create(['sexo' => 'M']))->create();

        $this->cantidadServicios = random_int(1, 10);
    }


    /**
     * A basic feature test example.
     */
    public function test_servicios_efectivos_en_la_vaca(): void
    {
        Servicio::factory()
            ->count($this->cantidadServicios)
            ->for($this->ganado)
            ->for($this->toro, 'servicioable')
            ->create(['personal_id' => $this->veterinario]);

        Parto::factory()
            ->count(random_int(1, $this->cantidadServicios))
            ->for($this->ganado)
            ->for(Ganado::factory()->for($this->finca)->hasAttached(Estado::firstWhere('estado', 'sano')), 'ganado_cria')
            ->for($this->toro, 'partoable')
            ->create(['personal_id' => $this->veterinario]);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/ganado/%s', $this->ganado->id));

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->where(
                'efectividad',
                fn($efectividad): bool => $efectividad >= 1 && $efectividad <= 100
            )
                ->whereType('efectividad', ['integer', 'double', 'null'])->etc()
        );
    }
    public function test_servicios_efectivos_del_toro(): void
    {
        Servicio::factory()
            ->count($this->cantidadServicios)
            ->for($this->ganado)
            ->sequence(fn(Sequence $sequence): array => ['fecha' => now()->subDays(random_int(1, 30))->subMonths(random_int(1, 3))])
            ->for($this->toro, 'servicioable')
            ->create(['personal_id' => $this->veterinario]);

        Parto::factory()
            ->count(random_int(1, $this->cantidadServicios))
            ->for($this->ganado)
            ->sequence(fn(Sequence $sequence): array => ['fecha' => now()->subDays(random_int(1, 30))->subMonths(random_int(1, 3))])
            ->for(Ganado::factory()->for($this->finca)->hasAttached(Estado::firstWhere('estado', 'sano')), 'ganado_cria')
            ->for($this->toro, 'partoable')
            ->create(['personal_id' => $this->veterinario]);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/toro/%s', $this->toro->id));

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->where(
                'toro.efectividad',
                fn($efectividad): bool => $efectividad >= 1 && $efectividad <= 100
            )
                ->whereType('toro.efectividad', ['integer', 'double', 'null'])->etc()
        );
    }
}
