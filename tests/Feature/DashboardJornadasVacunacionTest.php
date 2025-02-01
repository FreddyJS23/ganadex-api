<?php

namespace Tests\Feature;

use App\Models\Finca;
use App\Models\Jornada_vacunacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class DashboardJornadasVacunacionTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $finca;

    private int $cantidad_jornadasVacunacion = 10;

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

    private function generarJornadasVacunacion(): Collection
    {
        return Jornada_vacunacion::factory()
            ->count($this->cantidad_jornadasVacunacion)
            ->for($this->finca)
            ->create();
    }

    /**
     * A basic feature test example.
     */
    public function test_proximas_jornadas_vacunacion(): void
    {
        $this->generarJornadasVacunacion();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardJornadasVacunacion.proximasJornadasVacunacion'));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->whereType('proximas_jornadas_vacunacion', 'array')
                ->has(
                    'proximas_jornadas_vacunacion.0',
                    fn (AssertableJson $json)
                    => $json->whereAllType([
                        'vacuna' => 'string',
                        'prox_dosis' => 'string',
                        'ganado_vacunado' => 'array',
                    ])
                )
        );
    }

}
