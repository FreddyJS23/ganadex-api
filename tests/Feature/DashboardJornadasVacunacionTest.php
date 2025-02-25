<?php

namespace Tests\Feature;

use App\Models\Jornada_vacunacion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\Common\NeedsFinca;
use Tests\TestCase;

class DashboardJornadasVacunacionTest extends TestCase
{
    use RefreshDatabase;
    use NeedsFinca;

    private int $cantidad_jornadasVacunacion = 10;

    private function generarJornadasVacunacion(): Collection
    {
        return Jornada_vacunacion::factory()
            ->count($this->cantidad_jornadasVacunacion)
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

    public function test_proximas_jornadas_vacunacion(): void
    {
        $this->generarJornadasVacunacion();

        $this
            ->setUpRequest()
            ->getJson(route('dashboardJornadasVacunacion.proximasJornadasVacunacion'))
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): AssertableJson => $json
                    ->whereType('proximas_jornadas_vacunacion', 'array')
                    ->has(
                        key: 'proximas_jornadas_vacunacion.0',
                        length: fn(AssertableJson $json): AssertableJson => $json->whereAllType([
                            'vacuna' => 'string',
                            'prox_dosis' => 'string',
                            'ganado_vacunado' => 'array',
                        ])
                    )
            );
    }
}
