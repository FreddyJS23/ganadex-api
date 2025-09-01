<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Fallecimiento;
use App\Models\Ganado;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\Common\NeedsEstado;
use Tests\Feature\Common\NeedsSetupRequest;
use Tests\TestCase;

class DashboardFallecimientosTest extends TestCase
{
    use NeedsSetupRequest,
    NeedsEstado{
        NeedsEstado::setUp as needsEstadoSetUp;
        NeedsSetupRequest::setUp as needsSetupRequestSetUp;
    }


    private int $cantidad_fallecimientos = 50;

    protected function setUp(): void
    {
        $this->needsSetupRequestSetUp();
        $this->needsEstadoSetUp();
    }

    private function generarFallecimiento(): Collection
    {
        return Fallecimiento::factory()
            ->count($this->cantidad_fallecimientos)
            ->for(Ganado::factory()->for($this->hacienda)->hasAttached($this->estado))
            ->create();
    }


    public function test_causas_de_muertes_mas_frecuentes(): void
    {
        $this->generarFallecimiento();

        $this
            ->setUpRequest()
            ->getJson(route('dashboardFallecimientos.causasMuertesFrecuentes'))
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): AssertableJson =>  $json
                    ->where('total_fallecidos', $this->cantidad_fallecimientos)
                    ->whereType('causas_frecuentes', 'array')
                    ->whereAllType(
                        [
                            'causas_frecuentes.0.fallecimientos' => 'integer',
                            'causas_frecuentes.0.causa' => 'string'
                        ]
                    )
            );
    }

    public function test_error_caso_que_no_haya_muertes_para_sacar_causas_de_muertes_mas_frecuentes(): void
    {
        $this
            ->setUpRequest()
            ->getJson(route('dashboardFallecimientos.causasMuertesFrecuentes'))
            ->assertStatus(200);
    }
}
