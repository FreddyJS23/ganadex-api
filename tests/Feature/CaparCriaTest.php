<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Ganado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\Common\NeedsFinca;
use Tests\Feature\Common\NeedsGanado;
use Tests\TestCase;

class CaparCriaTest extends TestCase
{
    use RefreshDatabase;
    use NeedsGanado;
    use NeedsFinca;

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

    private function setUpRequest(): static
    {
        $this
            ->actingAs($this->user)
            ->withSession($this->getSessionInitializationArray());

        return $this;
    }

    public function test_obtener_crias_pendientes_capar(): void
    {
        $this->generarGanado();

        $this
            ->setUpRequest()
            ->getJson(route('capar.index'))
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json) => $json->has(
                key: 'crias_pendiente_capar',
                length: $this->cantidad_ganado
            ));
    }

    public function test_capar_cria(): void
    {
        $criasGanado = $this->generarGanado();
        $idRandom = random_int(0, $this->cantidad_ganado - 1);
        $idCria = $criasGanado[$idRandom]->id;

        //capar
        $this
            ->setUpRequest()
            ->getJson(route('capar.capar', ['ganado' => $idCria]));

        $this
            ->setUpRequest()
            ->getJson(sprintf('api/ganado/%s', $idCria))
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json) => $json
                    ->where(
                        key: 'ganado.estados',
                        expected: fn(Collection $estados) => $estados->doesntContain(
                            key: 'estado',
                            operator: 'pendiente_capar'
                        )
                    )
                    ->etc()
            );
    }
}
