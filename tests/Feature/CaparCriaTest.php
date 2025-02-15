<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Finca;
use App\Models\Ganado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class CaparCriaTest extends TestCase
{
    use RefreshDatabase;

    private int $cantidad_ganado = 10;

    private $user;
    private $ganado;
    private $estado;
    private $finca;


    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->hasConfiguracion()->create();

            $this->finca
            = Finca::factory()
            ->for($this->user)
            ->create();
    }

    private function generarGanado(): Collection
    {
        $this->estado = Estado::where('estado', 'pendiente_capar')->get();

        return Ganado::factory()
            ->count($this->cantidad_ganado)
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->finca)
            ->create();
    }

    /**
     * A basic feature test example.
     */
    public function test_obtener_crias_pendientes_capar(): void
    {
        $this->generarGanado();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('capar.index'));
        $response->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->has('crias_pendiente_capar', $this->cantidad_ganado));
    }

    public function test_capar_cria(): void
    {
        $criasGanado = $this->generarGanado();
        $idRandom = random_int(0, $this->cantidad_ganado - 1);
        $idCria = $criasGanado[$idRandom]->id;

        //capar
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('capar.capar', ['ganado' => $idCria]));

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/ganado/%s', $idCria));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json
                ->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->doesntContain('estado', 'pendiente_capar')
                )
                ->etc()
        );
    }
}
