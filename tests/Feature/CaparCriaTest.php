<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Ganado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\Common\NeedsEstado;
use Tests\Feature\Common\NeedsHacienda;
use Tests\Feature\Common\NeedsGanado;
use Tests\Feature\Common\NeedsSetupRequest;
use Tests\TestCase;

class CaparCriaTest extends TestCase
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
        $this->generarGanado();
    }

    public function test_obtener_crias_pendientes_capar(): void
    {
        $this->generarGanados(estado: $this->estadoPendienteCapar);

        $this
            ->setUpRequest()
            ->getJson(route('capar.index'))
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json): AssertableJson => $json->has(
                key: 'crias_pendiente_capar',
                length: $this->cantidad_ganado
            ));
    }

    public function test_capar_cria(): void
    {
        $criasGanado = $this->generarGanados(estado: $this->estadoPendienteCapar);
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
                fn(AssertableJson $json): AssertableJson => $json
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
