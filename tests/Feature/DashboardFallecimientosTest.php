<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Fallecimiento;
use App\Models\Finca;
use App\Models\Ganado;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class DashboardFallecimientosTest extends TestCase
{
    use RefreshDatabase;

    private int $cantidad_fallecimientos = 50;
    private $estado;
    private $user;
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

        $this->estado = Estado::all();
    }

    private function generarFallecimiento(): Collection
    {
        return Fallecimiento::factory()
            ->count($this->cantidad_fallecimientos)
            ->for(Ganado::factory()->for($this->finca)->hasAttached($this->estado))
            ->sequence(
                ['causa' => 'enferma'],
                ['causa' => 'accidente'],
                ['causa' => 'accidente2'],
                ['causa' => 'accidente3'],
                ['causa' => 'accidente4'],
                ['causa' => 'accidente5'],
                ['causa' => 'accidente6'],
                ['causa' => 'accidente7'],
                )
            ->create();
    }


    /**
     * A basic feature test example.
     */

    public function test_causas_de_muertes_mas_frecuentes(): void
    {
        $this->generarFallecimiento();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardFallecimientos.causasMuertesFrecuentes'));
        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) =>  $json

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

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardFallecimientos.causasMuertesFrecuentes'));
        $response->assertStatus(200);
    }
}
