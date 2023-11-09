<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Ganado;
use App\Models\Insumo;
use App\Models\Leche;
use App\Models\Personal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private int $cantidad_elementos = 50;
    private $estado;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();
        $this->estado = Estado::all();
    }
    private function generarGanado(): Collection
    {
        return Ganado::factory()
            ->count($this->cantidad_elementos)
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)            
            ->has(
                Leche::factory()->for($this->user)->state(
                    function (array $attributes, Ganado $ganado) {
                        return ['ganado_id' => $ganado->id, 'fecha' => Carbon::now()->format('Y-m-d')];
                    }
                ),
                'pesajes_leche'
            )
            ->state(new Sequence(
                fn (Sequence $sequence) => ['tipo_id' => rand(1, 5)]
            ))
            ->for($this->user)
            ->create();
    }

    private function generarGanadoPesajeLecheAnual(): Collection
    {
        //generar una fecha de produccion lactea
        function mesesPesajeAnual()
        {
            $mes = rand(0, 11);

            $fechaInicial = Carbon::create(2023, 1, 20);

            $fechaConMesAñadido = $fechaInicial->addMonths($mes)->format('Y-m-d');

            return $mes == 0 ? $fechaInicial->format('Y-m-d') : $fechaConMesAñadido;
        };

        return Ganado::factory()
            ->count($this->cantidad_elementos)
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            /* habra veces que se repita una fecha, por ende se crea 50 elementos ganado, 
            con 12 elementos de produccion lactea que serian la cantidad de meses que existen, 
            asi siempre todos los meses estaran cubiertos por lo menos una vez */
            ->has(
                Leche::factory()->for($this->user)->count(12)->state(
                    function (array $attributes, Ganado $ganado) {
                        return ['ganado_id' => $ganado->id];
                    }
                )->sequence(fn (Sequence $sequence) => ['fecha' => mesesPesajeAnual()]),
                'pesajes_leche'
            )
            ->for($this->user)
            ->create();
    }

    private function generarPersonal(): Collection
    {
        return Personal::factory()
            ->count($this->cantidad_elementos)
            ->for($this->user)
            ->create();
    }

    private function generarInsumos(): Collection
    {
        return Insumo::factory()
            ->count($this->cantidad_elementos)
            ->for($this->user)
            ->create();
    }

    /**
     * A basic feature test example.
     */
    public function test_total_ganado_por_tipo(): void
    {
        $this->generarGanado();
        $response = $this->actingAs($this->user)->getJson('api/total_ganado_tipo');

        $response->assertStatus(200)->assertJson(['total_tipos_ganado' => true]);
    }

    public function test_total_personal(): void
    {
        $this->generarPersonal();
        $response = $this->actingAs($this->user)->getJson('api/total_personal');

        $response->assertStatus(200)->assertJson(['total_personal' => $this->cantidad_elementos]);
    }

    public function test_total_vacas_en_gestacion(): void
    {
        $this->generarGanado();
        $response = $this->actingAs($this->user)->getJson('api/vacas_gestacion');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) => $json->whereType('vacas_en_gestacion', 'integer'));
    }

    public function test_ranking_top_3_vacas_mas_productoras(): void
    {
        $this->generarGanado();
        $response = $this->actingAs($this->user)->getJson('api/vacas_productoras');

        $response->assertStatus(200)->assertJson(['top_vacas_productoras' => true]);
    }

    public function test_ranking_top_3_vacas_menos_productoras(): void
    {
        $this->generarGanado();
        $response = $this->actingAs($this->user)->getJson('api/vacas_menos_productoras');

        $response->assertStatus(200)->assertJson(['top_vacas_menos_productoras' => true]);
    }

    public function test_total_vacas_pendientes_de_revision(): void
    {
        $this->generarGanado();
        $response = $this->actingAs($this->user)->getJson('api/ganado_pendiente_revision');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) => $json->whereType('ganado_pendiente_revision', 'integer'));
    }

    public function test_total_novillas_pendientes_de_servicio_o_monta(): void
    {
        $this->generarGanado();
        $response = $this->actingAs($this->user)->getJson('api/cantidad_novillas_montar');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) => $json->whereType('cantidad_vacas_para_servir', 'integer'));
    }

    public function test_menor_cantidad_insumo(): void
    {
        $this->generarInsumos();
        $response = $this->actingAs($this->user)->getJson('api/menor_insumo');

        $response->assertStatus(200)->assertJson(['menor_cantidad_insumo' => true]);
    }

    public function test_mayor_cantidad_insumo(): void
    {
        $this->generarInsumos();
        $response = $this->actingAs($this->user)->getJson('api/mayor_insumo');

        $response->assertStatus(200)->assertJson(['mayor_cantidad_insumo' => true]);
    }

    public function test_balance_anual_leche(): void
    {
        $this->generarGanadoPesajeLecheAnual();
        $response = $this->actingAs($this->user)->getJson('api/balance_anual_leche');


        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) => $json->has('balance_anual', 12));
    }
}
