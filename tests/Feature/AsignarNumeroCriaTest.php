<?php

namespace Tests\Feature;

use App\Models\Ganado;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;
use Illuminate\Support\Str;

class AsignarNumeroCriaTest extends TestCase
{
    use RefreshDatabase;

    private int $cantidad_ganado = 10;

    private $user;
    private $ganado;


    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();
    }

    private function generarGanado(): Collection
    {
        return Ganado::factory()
            ->count($this->cantidad_ganado)
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasEstado(1, ['estado' => '-pendiente_numeracion'])
            ->for($this->user)
            ->create(['numero' => null]);
    }

    /**
     * A basic feature test example.
     */
    public function test_obtener_crias_pendientes_numeracion(): void
    {
        $this->generarGanado();

        $response = $this->actingAs($this->user)->getJson(route('numeracion.index'));
        $response->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->has('crias_pendiente_numeracion', $this->cantidad_ganado));
    }

    public function test_asignar_numero_cria(): void
    {
        $criasGanado = $this->generarGanado();
        $idRandom = rand(0, $this->cantidad_ganado - 1);
        $idCria = $criasGanado[$idRandom]->id;

        //asignar numero
        $this->actingAs($this->user)->postJson(route('numeracion.store', ['ganado' => $idCria]), ['numero' => rand(1, 999)]);

        $response = $this->actingAs($this->user)->getJson(sprintf('api/ganado/%s', $idCria));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json
                ->where(
                    'ganado.estado',
                    fn (string $estado) => !Str::contains($estado, '-pendiente_numeracion')

                )->whereType('ganado.numero', 'integer')
                ->etc()
        );
    }
}
