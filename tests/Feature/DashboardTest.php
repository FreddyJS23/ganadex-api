<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Finca;
use App\Models\Ganado;
use App\Models\GanadoDescarte;
use App\Models\Insumo;
use App\Models\Leche;
use App\Models\Personal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private int $cantidad_elementos = 50;
    private $estado;
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
    private function generarGanado(): Collection
    {
        GanadoDescarte::factory()
            ->count(10)
            ->for($this->finca)
            ->forGanado(['finca_id' => $this->finca->id, 'sexo' => 'M', 'tipo_id' => 4])
            ->create();

        return Ganado::factory()
            ->count($this->cantidad_elementos)
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->has(
                Leche::factory()->for($this->finca)->state(
                    fn(array $attributes, Ganado $ganado) => ['ganado_id' => $ganado->id, 'fecha' => Carbon::now()->format('Y-m-d')]
                ),
                'pesajes_leche'
            )
            ->state(new Sequence(
                fn (Sequence $sequence) => ['tipo_id' => random_int(1, 4)]
            ))
            ->for($this->finca)
            ->create();
    }

  //generar una fecha de produccion lactea
    private function mesesPesajeAnual(int $año)
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
                Leche::factory()->for($this->finca)->count(12)->state(
                    fn(array $attributes, Ganado $ganado) => ['ganado_id' => $ganado->id]
                )->sequence(fn (Sequence $sequence) => ['fecha' => $this->mesesPesajeAnual($año)]),
                'pesajes_leche'
            )
            ->for($this->finca)
            ->create();
    }

    private function generarPersonal(): Collection
    {
        return Personal::factory()
            ->count($this->cantidad_elementos)
            ->for($this->finca)
            ->create();
    }

    private function generarInsumos(): Collection
    {
        return Insumo::factory()
            ->count($this->cantidad_elementos)
            ->for($this->finca)
            ->create();
    }

    /**
     * A basic feature test example.
     */
    public function test_total_ganado_por_tipo(): void
    {
        $this->generarGanado();
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardPrincipal.totalGanadoTipo'));

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->whereType('total_tipos_ganado', 'array')
            ->where('total_tipos_ganado', fn (SupportCollection $tipos) => count($tipos) == 9 ? true : false)
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
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardPrincipal.totalPersonal'));

        $response->assertStatus(200)->assertJson(['total_personal' => $this->cantidad_elementos]);
    }

    public function test_total_vacas_en_gestacion(): void
    {
        $this->generarGanado();
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardPrincipal.vacasEnGestacion'));

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) => $json->whereType('vacas_en_gestacion', 'integer'));
    }

    public function test_ranking_top_3_vacas_mas_productoras(): void
    {
        $this->generarGanado();
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardPrincipal.topVacasProductoras'));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->whereType('top_vacas_productoras', 'array')
                ->where('top_vacas_productoras', fn (SupportCollection $top) => count($top) == 3 ? true : false)
                ->has(
                    'top_vacas_productoras.0',
                    fn (AssertableJson $json)
                    => $json->whereAllType(['peso_leche' => 'integer'])
                    ->has(
                        'ganado',
                        fn (AssertableJson $json)
                        => $json->whereAllType(['id' => 'integer', 'numero' => 'integer'])
                    )
                )
        );
    }

    public function test_ranking_top_3_vacas_menos_productoras(): void
    {
        $this->generarGanado();
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardPrincipal.topVacasMenosProductoras'));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->whereType('top_vacas_menos_productoras', 'array')
                ->where('top_vacas_menos_productoras', fn (SupportCollection $top) => count($top) == 3 ? true : false)
                ->has(
                    'top_vacas_menos_productoras.0',
                    fn (AssertableJson $json)
                    => $json->whereAllType(['peso_leche' => 'integer'])
                    ->has(
                        'ganado',
                        fn (AssertableJson $json)
                        => $json->whereAllType(['id' => 'integer', 'numero' => 'integer'])
                    )
                )
        );
    }

    public function test_total_vacas_pendientes_de_revision(): void
    {
        $this->generarGanado();
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardPrincipal.totalGanadoPendienteRevision'));

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) => $json->whereType('ganado_pendiente_revision', 'integer'));
    }

    public function test_total_novillas_pendientes_de_servicio_o_monta(): void
    {
        $this->generarGanado();
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardPrincipal.cantidadVacasParaServir'));

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) => $json->whereType('cantidad_vacas_para_servir', 'integer'));
    }
/*
    public function test_menor_cantidad_insumo(): void
    {
        $this->generarInsumos();
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardPrincipal.insumoMenorExistencia'));

        $response->assertStatus(200)->assertJson(fn (AssertableJSon $json) =>
        $json->whereAllType([
            'menor_cantidad_insumo.id' => 'integer',
            'menor_cantidad_insumo.insumo' => 'string',
            'menor_cantidad_insumo.cantidad' => 'integer',
        ]));
    }

    public function test_mayor_cantidad_insumo(): void
    {
        $this->generarInsumos();
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardPrincipal.insumoMayorExistencia'));

        $response->assertStatus(200)->assertJson(fn (AssertableJSon $json) =>
        $json->whereAllType([
            'mayor_cantidad_insumo.id' => 'integer',
            'mayor_cantidad_insumo.insumo' => 'string',
            'mayor_cantidad_insumo.cantidad' => 'integer',
        ]));
    } */

    public function test_balance_anual_leche(): void
    {
        $this->generarGanadoPesajeLecheAnual(now()->format('Y'));
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardPrincipal.balanceAnualProduccionLeche'));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->has('balance_anual', 12)
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
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('dashboardPrincipal.balanceAnualProduccionLeche', ['year' => now()->addYear()->format('Y')]));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->has('balance_anual', 12)
                 ->whereAllType(
                     [
                         'balance_anual.0.mes' => 'string',
                         'balance_anual.0.promedio_mensual' => 'integer'
                     ]
                 )
        );
    }
}
