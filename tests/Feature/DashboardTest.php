<?php

namespace Tests\Feature;


use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\Common\NeedsEstado;
use Tests\Feature\Common\NeedsGanado;
use Tests\Feature\Common\NeedsGanadoDescarte;
use Tests\Feature\Common\NeedsPersonal;
use Tests\Feature\Common\NeedsSetupRequest;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use NeedsSetupRequest,
        NeedsPersonal,
        NeedsGanado,
        NeedsEstado,
        NeedsGanadoDescarte {
        NeedsSetupRequest::setUp as needsSetupRequestSetUp;
        NeedsEstado::setUp as needsEstadoSetUp;
    }

    private int $cantidad_elementos = 50;

    protected function setUp(): void
    {
        $this->needsSetupRequestSetUp();
        $this->needsEstadoSetUp();
    }


    public function test_total_ganado_por_tipo(): void
    {
        $this->generarGanadoConPesajesLeche();
        $this->generarGanadoDescartes();

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
                    'total_tipos_ganado.0.Becerra' => 'integer',
                    'total_tipos_ganado.1.Mauta' => 'integer',
                    'total_tipos_ganado.2.Novilla' => 'integer',
                    'total_tipos_ganado.3.Adulta' => 'integer',
                    'total_tipos_ganado.4.Becerro' => 'integer',
                    'total_tipos_ganado.5.Maute' => 'integer',
                    'total_tipos_ganado.6.Novillo' => 'integer',
                    'total_tipos_ganado.7.Adulto' => 'integer',
                    'total_tipos_ganado.8.Descarte' => 'integer',
                ]));
    }

    public function test_total_personal(): void
    {
        $this->generarPersonal($this->cantidad_elementos);

        $this
            ->setUpRequest()
            ->getJson(route('dashboardPrincipal.totalPersonal'))
            ->assertStatus(200)
            //+1 porque un veterinario se esta creando en el setUp de NeedsVeterinario
            ->assertJson(['total_personal' => $this->cantidad_elementos + 1 ]);
    }

    public function test_total_vacas_en_gestacion(): void
    {
        $this->generarGanados();

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
        $this->generarGanadoConPesajesLeche();

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
                            ->whereAllType(['peso_leche' => 'string'])
                            ->has(
                                'ganado',
                                fn(AssertableJson $json): AssertableJson => $json
                                    ->whereAllType([
                                        'id' => 'integer',
                                        'numero' => 'integer|null'
                                    ])
                            )
                    )
            );
    }

    public function test_ranking_top_3_vacas_menos_productoras(): void
    {
        $this->generarGanadoConPesajesLeche();

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
                            ->whereAllType(['peso_leche' => 'string'])
                            ->has(
                                'ganado',
                                fn(AssertableJson $json): AssertableJson => $json
                                    ->whereAllType([
                                        'id' => 'integer',
                                        'numero' => 'integer|null'
                                    ])
                            )
                    )
            );
    }

    public function test_total_vacas_en_ordeño(): void
    {
        $this->generarGanados();

        $this
            ->setUpRequest()
            ->getJson(route('dashboardPrincipal.totalVacasEnOrdeño'))
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json): AssertableJson => $json
                ->whereType('total_vacas_en_ordeño', 'integer'));
    }

    public function test_total_vacas_pendientes_de_revision(): void
    {
        $this->generarGanados();

        $this
            ->setUpRequest()
            ->getJson(route('dashboardPrincipal.totalGanadoPendienteRevision'))
            ->assertStatus(200)
            ->assertJson(fn(AssertableJson $json): AssertableJson => $json
                ->whereType('ganado_pendiente_revision', 'integer'));
    }

    public function test_total_novillas_pendientes_de_servicio_o_monta(): void
    {
        $this->generarGanados();

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
