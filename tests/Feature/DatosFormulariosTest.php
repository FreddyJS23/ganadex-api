<?php

namespace Tests\Feature;

use App\Models\Cargo;
use App\Models\Comprador;
use App\Models\Ganado;
use App\Models\Hacienda;
use App\Models\Leche;
use App\Models\Personal;
use App\Models\UsuarioVeterinario;
use App\Models\Vacuna;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\Common\NeedsEstado;
use Tests\Feature\Common\NeedsHacienda;
use Tests\TestCase;

enum cargosPersonal: int{
    case obrero = 1;
    case veterinario = 2;
}

class DatosFormulariosTest extends TestCase
{
    use RefreshDatabase;

    use NeedsHacienda {
        NeedsHacienda::setUp as needsHaciendaSetUp;
    }

    use NeedsEstado {
        NeedsEstado::setUp as needsEstadoSetUp;
    }

    private int $cantidad_ganado = 50;

    protected function setUp(): void
    {
        $this->needsHaciendaSetUp();
        $this->needsEstadoSetUp();
    }

    private function generarGanado(): Collection
    {
        return Ganado::factory()
            ->count($this->cantidad_ganado)
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->hacienda)
            ->create();
    }

    private function generarPersonal(CargosPersonal $cargoPersonal): Collection
    {
        return Personal::factory()
            ->count(10)
            ->for($this->user)
            ->hasAttached($this->hacienda)
            ->create(['cargo_id' => $cargoPersonal->value]);
    }

    private function setUpRequest(): static
    {
        $this
            ->actingAs($this->user)
            ->withSession($this->getSessionInitializationArray());

        return $this;
    }

    public function test_obtener_novillas_que_se_pueden_servir(): void
    {
        $this->generarGanado();

        $this
            ->setUpRequest()
            ->getJson(route('datosParaFormularios.novillasParaMontar'))
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): AssertableJson => $json
                    ->whereType('novillas_para_servicio', 'array')
                    ->where(
                        'novillas_para_servicio',
                        fn(SupportCollection $novillasParaServir): bool => count($novillasParaServir) > 1
                    )
                    ->has(
                        'novillas_para_servicio.0',
                        fn(AssertableJson $json): AssertableJson => $json->whereAllType([
                            'id' => 'integer',
                            'numero' => 'integer|null',
                            'peso_actual' => 'string'
                        ])
                    )
            );
    }

    public function test_obtener_años_de_ventas_de_ganados(): void
    {
        Venta::factory()
            ->count(10)
            ->for($this->hacienda)
            ->for(
                Ganado::factory()
                    ->for($this->hacienda)
                    ->hasPeso(1)
                    ->hasAttached($this->estado)
                    ->create()
            )
            ->for(Comprador::factory()->for($this->hacienda)->create())
            ->create();

        $this
            ->setUpRequest()
            ->getJson(route('datosParaFormularios.añosVentasGanado'))
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): AssertableJson => $json
                    ->whereType('años_ventas_ganado', 'array')
                    ->has(
                        'años_ventas_ganado.0',
                        fn(AssertableJson $json): AssertableJson => $json->whereAllType([
                            'año' => 'integer',
                        ])
                    )
            );
    }

    public function test_obtener_años_de_produccion_de_leches(): void
    {
        Leche::factory()
            ->count(10)
            ->for(
                Ganado::factory()
                    ->for($this->hacienda)
                    ->hasPeso(1)
                    ->hasAttached($this->estado)
                    ->create()
            )
            ->for($this->hacienda)
            ->create();

        $this
            ->setUpRequest()
            ->getJson(route('datosParaFormularios.añosProduccionLeche'))
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): AssertableJson => $json
                    ->whereType('años_produccion_leche', 'array')
                    ->has(
                        'años_produccion_leche.0',
                        fn(AssertableJson $json): AssertableJson => $json->whereAllType([
                            'año' => 'integer',
                        ])
                    )
            );
    }

    public function test_obtener_vacunas_disponibles(): void
    {
        Vacuna::factory()
            ->count(10)
            ->create();

        $this
            ->setUpRequest()
            ->getJson(route('datosParaFormularios.vacunasDisponibles'))
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): AssertableJson => $json
                    ->whereType('vacunas_disponibles', 'array')
                    ->has(
                        'vacunas_disponibles.0',
                        fn(AssertableJson $json): AssertableJson => $json->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string',
                            'intervalo_dosis' => 'integer',
                            'tipo_vacuna' => 'string',
                            'dosis_recomendada_anual' => 'integer|null',
                            'aplicable_a_todos' => 'boolean',
                            'tipos_ganado' => 'array',
                        ])
                    )
                    //vacuna aplicable a algunos tipos de ganado
                    ->has(
                        'vacunas_disponibles.3.tipos_ganado.0',
                        fn(AssertableJson $json): AssertableJson => $json->whereAllType([
                            'tipo' => 'string',
                            'sexo' => 'string',
                        ])->etc()
                    )
            );
    }

    public function test_obtener_numero_disponible_en_DB(): void
    {
        $this->generarGanado();

        $this
            ->setUpRequest()
            ->getJson(route('datosParaFormularios.sugerirNumeroDisponibleEnBD'))
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): AssertableJson => $json->whereType(
                    'numero_disponible',
                    'integer'
                )
            );
    }

    public function test_obtener_veterinarios_sin_usuario(): void
    {
        UsuarioVeterinario::factory()
            ->count(10)
            ->for(Personal::factory()->hasAttached($this->hacienda)->for($this->user)->create(['cargo_id' => 2]), 'veterinario')
            ->create(['admin_id' => $this->user->id]);

        Personal::factory()
            ->count(10)
            ->for($this->user)
            ->hasAttached($this->hacienda)
            ->create(['cargo_id' => 2]);

        $this
            ->setUpRequest()
            ->getJson(route('datosParaFormularios.veterinariosSinUsuario'))
            ->assertStatus(200)->assertJson(
                fn(AssertableJson $json): AssertableJson => $json
                    ->whereType('veterinarios_sin_usuario', 'array')
                    ->has(
                        'veterinarios_sin_usuario',
                        10,
                        fn(AssertableJson $json): AssertableJson => $json->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string',
                        ])
                    )
            );
    }

    public function test_obtener_veterinarios_select(): void
    {
        $this->generarPersonal(CargosPersonal::veterinario);

        $otraHacienda = Hacienda::factory()
        ->for($this->user)
        ->create(['nombre' => 'otra_hacienda']);

        Personal::factory()
        ->count(20)
        ->for($this->user)
        ->hasAttached($otraHacienda)
        ->create(['cargo_id' => 2]);


        $this
            ->setUpRequest()
            ->getJson(route('datosParaFormularios.veterinariosDisponibles'))
            ->assertStatus(200)->assertJson(
                fn(AssertableJson $json): AssertableJson => $json
                    ->whereType('veterinarios', 'array')
                    ->has(
                        'veterinarios',
                        20,/* deberian haber 20, ya que solo se trae veterinarios que no se hayan registrado en hacienda en sesion */
                        fn(AssertableJson $json): AssertableJson => $json->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string',
                        ])
                    )
            );
    }

    public function test_obtener_veterinarios_hacienda_actual_select(): void
    {
        $this->generarPersonal(CargosPersonal::veterinario);

        $otraHacienda = Hacienda::factory()
        ->for($this->user)
        ->create(['nombre' => 'otra_hacienda']);

        Personal::factory()
        ->count(10)
        ->for($this->user)
        ->hasAttached($otraHacienda)
        ->create(['cargo_id' => 2]);

        $this
            ->setUpRequest()
            ->getJson(route('datosParaFormularios.veterinariosDisponiblesHaciendaActual'))
            ->assertStatus(200)->assertJson(
                fn(AssertableJson $json): AssertableJson => $json
                    ->whereType('veterinarios', 'array')
                    ->has(
                        'veterinarios.0',
                        fn(AssertableJson $json): AssertableJson => $json->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string',
                        ])
                    )
            );
    }

    public function test_obtener_obreros_select(): void
    {
        $this->generarPersonal(CargosPersonal::obrero);

        $this
            ->setUpRequest()
            ->getJson(route('datosParaFormularios.obrerosDisponibles'))
            ->assertStatus(200)->assertJson(
                fn(AssertableJson $json): AssertableJson => $json
                    ->whereType('obreros', 'array')
                    ->has(
                        'obreros.0',
                        fn(AssertableJson $json): AssertableJson => $json->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string',
                        ])
                    )
            );
    }

    public function test_obtener_origen_ganado(): void
    {
        $this
            ->setUpRequest()
            ->getJson(route('datosParaFormularios.origenGanado'))
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): AssertableJson => $json
                    ->whereType('origen_ganado', 'array')
                    ->has(
                        'origen_ganado.0',
                        fn(AssertableJson $json): AssertableJson => $json->whereAllType([
                            'id' => 'integer',
                            'origen' => 'string',
                        ])
                    )
            );
    }
}
