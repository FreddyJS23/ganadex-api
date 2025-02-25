<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Fallecimiento;
use App\Models\Ganado;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\Common\NeedsFinca;
use Tests\TestCase;

class DashboardFallecimientosTest extends TestCase
{
    use RefreshDatabase;

    use NeedsFinca {
        setUp as needsFincaSetUp;
    }

    private int $cantidad_fallecimientos = 50;
    private Collection $estado;

    protected function setUp(): void
    {
        $this->needsFincaSetUp();

        $this->estado = Estado::all();
    }

    private function generarFallecimiento(): Collection
    {
        return Fallecimiento::factory()
            ->count($this->cantidad_fallecimientos)
            ->for(Ganado::factory()->for($this->finca)->hasAttached($this->estado))
            ->create();
    }

    private function setUpRequest(): static
    {
        $this
            ->actingAs($this->user)
            ->withSession($this->getSessionInitializationArray());

        return $this;
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
