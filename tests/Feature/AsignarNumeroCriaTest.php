<?php

namespace Tests\Feature;

use App\Models\Ganado;
use Illuminate\Support\Collection;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\Common\NeedsEstado;
use Tests\Feature\Common\NeedsGanado;
use Tests\Feature\Common\NeedsSetupRequest;
use Tests\TestCase;

class AsignarNumeroCriaTest extends TestCase
{
    use NeedsSetupRequest,
        NeedsGanado,
        NeedsEstado
        {
            NeedsSetupRequest::setUp as needsSetupRequestSetUp;
            NeedsEstado::setUp as needsEstadoSetUp;
        }

        protected function setUp(): void
    {
        $this->needsSetupRequestSetUp();
        $this->needsEstadoSetUp();
    }

    private function generarGanadoSinNumero(): Collection
    {
        return Ganado::factory()
            ->count($this->cantidad_ganado)
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estadoPendienteNumeracion)
            ->for($this->hacienda)
            ->create(['numero' => null]);
    }

    public function test_obtener_crias_pendientes_numeracion(): void
    {
        $this->generarGanadoSinNumero();

        $this
            ->setUpRequest()
            ->getJson(route('numeracion.index'))
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json): AssertableJson => $json->has(
                key: 'crias_pendiente_numeracion',
                length: $this->cantidad_ganado
            ));
    }

    public function test_asignar_numero_cria(): void
    {
        $criasGanado = $this->generarGanadoSinNumero();
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
                fn(AssertableJson $json): AssertableJson => $json
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
