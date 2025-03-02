<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Ganado;
use App\Models\GanadoDescarte;
use App\Models\Insumo;
use App\Models\Leche;
use App\Models\Personal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\Common\NeedsHacienda;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    use NeedsHacienda {
        setUp as needsHaciendaSetUp;
    }

    private int $cantidad_elementos = 50;
    private Collection $estado;

    protected function setUp(): void
    {
        $this->needsHaciendaSetUp();

        $this->estado = Estado::all();
    }

    private function generarGanado(): Collection
    {
        GanadoDescarte::factory()
            ->count(10)
            ->for($this->hacienda)
            ->forGanado(['hacienda_id' => $this->hacienda->id, 'sexo' => 'M', 'tipo_id' => 4])
            ->create();

        return Ganado::factory()
            ->count($this->cantidad_elementos)
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->has(
                Leche::factory()->for($this->hacienda)->state(
                    fn(array $attributes, Ganado $ganado): array => [
                        'ganado_id' => $ganado->id,
                        'fecha' => Carbon::now()->format('Y-m-d')
                    ]
                ),
                'pesajes_leche'
            )
            ->state(new Sequence(fn(): array => ['tipo_id' => random_int(1, 4)]))
            ->for($this->hacienda)
            ->create();
    }

    //generar una fecha de produccion lactea
    private function mesesPesajeAnual(int $año): string
    {
        $mes = random_int(0, 11);
        $fechaInicial = Carbon::create($año, 1, 20);
        $fechaConMesAñadido = $fechaInicial->addMonths($mes)->format('Y-m-d');

        return $mes == 0 ? $fechaInicial->format('Y-m-d') : $fechaConMesAñadido;
    }

    private function generarGanadoPesajeLecheAnual(int $año): Collection
    {
        return Ganado::factory()
            ->count($this->cantidad_elementos)
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            /* habra veces que se repita una fecha, por ende se crea 50 elementos ganado,
            con 12 elementos de produccion lactea que serian la cantidad de meses que existen,
            asi siempre todos los meses estaran cubiertos por lo menos una vez */
            ->has(
                Leche::factory()
                    ->for($this->hacienda)
                    ->count(12)
                    ->state(
                        fn(array $attributes, Ganado $ganado): array => [
                            'ganado_id' => $ganado->id
                        ]
                    )
                    ->sequence(fn(): array => [
                        'fecha' => $this->mesesPesajeAnual($año)
                    ]),
                'pesajes_leche'
            )
            ->for($this->hacienda)
            ->create();
    }

    private function generarPersonal(): Collection
    {
        return Personal::factory()
            ->count($this->cantidad_elementos)
            ->for($this->hacienda)
            ->create();
    }

    private function generarInsumos(): Collection
    {
        return Insumo::factory()
            ->count($this->cantidad_elementos)
            ->for($this->hacienda)
            ->create();
    }

    private function setUpRequest(): static
    {
        $this
            ->actingAs($this->user)
            ->withSession($this->getSessionInitializationArray());

        return $this;
    }

    public function test_total_ganado_por_tipo(): void
    {
        $this->generarGanado();

        $this
            ->setUpRequest()
            ->getJson(route('dashboardPrincipal.totalGanadoTipo'))
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json): AssertableJson => $json
                ->whereType(
                    'total_tipos_ganado',
                    'array'
                )
                ->where(
                    'total_tipos_ganado',
                    fn(SupportCollection $tipos): bool => count($tipos) === 9
                )
                ->whereAllType([
                    'total_tipos_ganado.0.becerra' => 'integer',
                    'total_tipos_ganado.1.mauta' => 'integer',
                    'total_tipos_ganado.2.novilla' => 'integer',
                    'total_tipos_ganado.3.adulta' => 'integer',
                    'total_tipos_ganado.4.becerro' => 'integer',
                    'total_tipos_ganado.5.maute' => 'integer',
                    'total_tipos_ganado.6.novillo' => 'integer',
                    'total_tipos_ganado.7.adulto' => 'integer',
                    'total_tipos_ganado.8.descarte' => 'integer',
                ]));
    }

    public function test_total_personal(): void
    {
        $this->generarPersonal();

        $this
            ->setUpRequest()
            ->getJson(route('dashboardPrincipal.totalPersonal'))
            ->assertStatus(200)
            ->assertJson(['total_personal' => $this->cantidad_elementos]);
    }

    public function test_total_vacas_en_gestacion(): void
    {
        $this->generarGanado();

        $this
            ->setUpRequest()
            ->getJson(route('dashboardPrincipal.vacasEnGestacion'))
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json): AssertableJson => $json->whereType(
                key: 'vacas_en_gestacion',
                expected: 'integer'
            ));
    }

    public function test_ranking_top_3_vacas_mas_productoras(): void
    {
        $this->generarGanado();

        $this
            ->setUpRequest()
            ->getJson(route('dashboardPrincipal.topVacasProductoras'))
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): AssertableJson =>
                $json->whereType('top_vacas_productoras', 'array')
                    ->where(
                        key: 'top_vacas_productoras',
                        expected: fn(SupportCollection $top): bool => count($top) === 3
                    )
                    ->has(
                        'top_vacas_productoras.0',
                        fn(AssertableJson $json): AssertableJson => $json
                            ->whereAllType(['peso_leche' => 'integer'])
                            ->has(
                                'ganado',
                                fn(AssertableJson $json): AssertableJson => $json
                                    ->whereAllType([
                                        'id' => 'integer',
                                        'numero' => 'integer'
                                    ])
                            )
                    )
            );
    }

    public function test_ranking_top_3_vacas_menos_productoras(): void
    {
        $this->generarGanado();

        $this->setUpRequest()
            ->getJson(route('dashboardPrincipal.topVacasMenosProductoras'))
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): AssertableJson => $json
                    ->whereType('top_vacas_menos_productoras', 'array')
                    ->where(
                        key: 'top_vacas_menos_productoras',
                        expected: fn(SupportCollection $top): bool => count($top) === 3
                    )
                    ->has(
                        'top_vacas_menos_productoras.0',
                        fn(AssertableJson $json): AssertableJson => $json
                            ->whereAllType(['peso_leche' => 'integer'])
                            ->has(
                                'ganado',
                                fn(AssertableJson $json): AssertableJson => $json
                                    ->whereAllType([
                                        'id' => 'integer',
                                        'numero' => 'integer'
                                    ])
                            )
                    )
            );
    }

    public function test_total_vacas_pendientes_de_revision(): void
    {
        $this->generarGanado();

        $this
            ->setUpRequest()
            ->getJson(route('dashboardPrincipal.totalGanadoPendienteRevision'))
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json): AssertableJson => $json
                ->whereType('ganado_pendiente_revision', 'integer'));
    }

    public function test_total_novillas_pendientes_de_servicio_o_monta(): void
    {
        $this->generarGanado();

        $this
            ->setUpRequest()
            ->getJson(route('dashboardPrincipal.cantidadVacasParaServir'))
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json): AssertableJson => $json
                ->whereType('cantidad_vacas_para_servir', 'integer'));
    }

    /*public function test_menor_cantidad_insumo(): void
    {
        $this->generarInsumos();

        $this->setUpRequest()
            ->getJson(route('dashboardPrincipal.insumoMenorExistencia'))
            ->assertStatus(200)->assertJson(fn(AssertableJSon $json) => $json
                ->whereAllType([
                    'menor_cantidad_insumo.id' => 'integer',
                    'menor_cantidad_insumo.insumo' => 'string',
                    'menor_cantidad_insumo.cantidad' => 'integer',
                ]));
    }*/

    /*public function test_mayor_cantidad_insumo(): void
    {
        $this->generarInsumos();

        $this
            ->setUpRequest()
            ->getJson(route('dashboardPrincipal.insumoMayorExistencia'))
            ->assertStatus(200)
            ->assertJson(fn(AssertableJSon $json) => $json
                ->whereAllType([
                    'mayor_cantidad_insumo.id' => 'integer',
                    'mayor_cantidad_insumo.insumo' => 'string',
                    'mayor_cantidad_insumo.cantidad' => 'integer',
                ]));
    }*/

    public function test_balance_anual_leche(): void
    {
        $this->generarGanadoPesajeLecheAnual(now()->format('Y'));

        $this
            ->setUpRequest()
            ->getJson(route('dashboardPrincipal.balanceAnualProduccionLeche'))
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): AssertableJson => $json->has('balance_anual', 12)
                    ->whereAllType(
                        [
                            'balance_anual.0.mes' => 'string',
                            'balance_anual.0.promedio_mensual' => 'integer'
                        ]
                    )
            );
    }

    public function test_balance_anual_leche_con_parametro(): void
    {
        $this->generarGanadoPesajeLecheAnual(now()->addYear()->format('Y'));

        $this
            ->setUpRequest()
            ->getJson(route(
                'dashboardPrincipal.balanceAnualProduccionLeche',
                ['year' => now()->addYear()->format('Y')]
            ))
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): AssertableJson => $json->has('balance_anual', 12)
                    ->whereAllType(
                        [
                            'balance_anual.0.mes' => 'string',
                            'balance_anual.0.promedio_mensual' => 'integer'
                        ]
                    )
            );
    }
}
