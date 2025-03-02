<?php

namespace Tests\Feature;

use App\Models\Plan_sanitario;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\Common\NeedsHacienda;
use Tests\TestCase;

class DashboardPlanesSanitarioTest extends TestCase
{
    use RefreshDatabase;
    use NeedsHacienda;

    private int $cantidad_plaSanitario = 10;

    private function generarPlanesSanitario(): Collection
    {
        return Plan_sanitario::factory()
            ->count($this->cantidad_plaSanitario)
            ->for($this->hacienda)
            ->create(['prox_dosis' => now()->addDays(random_int(10,100))]);
    }

    private function setUpRequest(): static
    {
        $this
            ->actingAs($this->user)
            ->withSession($this->getSessionInitializationArray());

        return $this;
    }

    public function test_proximas_planes_sanitario(): void
    {
        $this->generarPlanesSanitario();

        $this
            ->setUpRequest()
            ->getJson(route('dashboardPlanesSanitario.proximosPlanesSanitario'))
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): AssertableJson => $json
                    ->whereType('proximos_planes_sanitario', 'array')
                    ->has(
                        key: 'proximos_planes_sanitario.0',
                        length: fn(AssertableJson $json): AssertableJson => $json->whereAllType([
                            'vacuna' => 'string',
                            'prox_dosis' => 'string',
                            'ganado_vacunado' => 'array',
                        ])
                    )
            );
    }
}
