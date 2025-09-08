<?php

namespace Tests\Feature;

use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\Common\NeedsEstado;
use Tests\Feature\Common\NeedsGanado;
use Tests\Feature\Common\NeedsParto;
use Tests\Feature\Common\NeedsPersonal;
use Tests\Feature\Common\NeedsServicio;
use Tests\Feature\Common\NeedsSetupRequest;
use Tests\Feature\Common\NeedsToro;
use Tests\TestCase;

class EfectividadTest extends TestCase
{
    use NeedsSetupRequest,
        NeedsEstado,
        NeedsGanado,
        NeedsPersonal,
        NeedsToro,
        NeedsServicio,
        NeedsParto {
            NeedsSetupRequest::setUp as needsSetupRequestSetUp;
            NeedsEstado::setUp as needsEstadoSetUp;
            NeedsPersonal::setUp as needsPersonalSetUp;
            NeedsToro::setUp as needsToroSetUp;
        }

    private int $cantidadServicios;

    protected function setUp(): void
    {
        $this->needsSetupRequestSetUp();
        $this->needsEstadoSetUp();
        $this->generarGanado();
        $this->needsPersonalSetUp();
        $this->needsToroSetUp();

        $this->cantidadServicios = random_int(1, 10);
    }


    /**
     * A basic feature test example.
     */
    public function test_servicios_efectivos_en_la_vaca(): void
    {
        $this->generarServicios($this->cantidadServicios, true);

        $this->generarPartos(random_int(1, $this->cantidadServicios), true);

        $response = $this->setUpRequest()
        ->getJson(sprintf('api/ganado/%s', $this->ganado->id));

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
        $this->generarServicios($this->cantidadServicios, true);

        $this->generarPartos(random_int(1, $this->cantidadServicios), true);

        $response = $this->setUpRequest()
        ->getJson(sprintf('api/toro/%s', $this->toro->id));

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->where(
                'toro.efectividad',
                fn($efectividad): bool => $efectividad >= 1 && $efectividad <= 100
            )
                ->whereType('toro.efectividad', ['integer', 'double', 'null'])->etc()
        );
    }
}
