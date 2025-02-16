<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Ganado;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\Common\NeedsFinca;
use Tests\Feature\Common\NeedsGanado;
use Tests\TestCase;

class AsignarNumeroCriaTest extends TestCase
{
    use RefreshDatabase;
    use NeedsGanado;
    use NeedsFinca;

    private function generarGanado(): Collection
    {
        $this->estado = Estado::where('estado', 'pendiente_numeracion')->get();

        return Ganado::factory()
            ->count($this->cantidad_ganado)
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->finca)
            ->create(['numero' => null]);
    }

    public function test_obtener_crias_pendientes_numeracion(): void
    {
        $this->generarGanado();

        $this
            ->setUpRequest()
            ->getJson(route('numeracion.index'))
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json) => $json->has(
                key: 'crias_pendiente_numeracion',
                length: $this->cantidad_ganado
            ));
    }

    public function test_asignar_numero_cria(): void
    {
        $criasGanado = $this->generarGanado();
        $idRandom = random_int(0, $this->cantidad_ganado - 1);
        $idCria = $criasGanado[$idRandom]->id;

        //asignar numero
        $this
            ->setUpRequest()
            ->postJson(
                uri: route('numeracion.store', ['ganado' => $idCria]),
                data: ['numero' => random_int(1, 999)]
            );

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
                            operator: 'pendiente_numeracion'
                        )
                    )
                    ->whereType('ganado.numero', 'integer')
                    ->etc()
            );
    }
}
