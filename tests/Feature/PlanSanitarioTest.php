<?php

namespace Tests\Feature;


use App\Models\Ganado;
use App\Models\Plan_sanitario;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Tests\Feature\Common\NeedsEstado;
use Tests\Feature\Common\NeedsSetupRequest;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class PlanSanitarioTest extends TestCase
{
    use NeedsSetupRequest,
        NeedsEstado {
        NeedsSetupRequest::setUp as needsSetupRequestSetUp;
        NeedsEstado::setUp as needsEstadoSetUp;
    }

    private array $planSanitario = [
        'fecha_inicio' => '2020-10-02',
        'fecha_fin' => '2020-10-02',
        'vacuna_id' => 1,
    ];

    private int $cantidad_PlanesSanitario = 10;


    protected function setUp(): void
    {
        $this->needsSetupRequestSetUp();
        $this->needsEstadoSetUp();

        Ganado::factory()
            ->count(30)
            ->for($this->hacienda)
            ->sequence(
                ['tipo_id' => 1],
                ['tipo_id' => 2],
                ['tipo_id' => 3],
                ['tipo_id' => 4],
            )
            ->hasAttached($this->estadoSano)
            ->create();
    }

    private function generarPlanSanitario(): Collection
    {
        return Plan_sanitario::factory()
            ->count($this->cantidad_PlanesSanitario)
            ->for($this->hacienda)
            ->create();
    }
    public static function ErrorInputProvider(): array
    {
        return [

            'caso de insertar datos erróneos' => [
                [
                    'fecha_inicio' => '20201002',
                    'fecha_fin' => '20201002',
                    'vacuna_id' => 9393,
                ],
                ['fecha_inicio', 'fecha_fin', 'vacuna_id']
            ],
            'caso de no insertar datos requeridos' => [
                [],
                ['fecha_inicio', 'fecha_fin', 'vacuna_id']
            ],
        ];
    }


    public function test_obtener_planes_sanitario(): void
    {
        $this->generarPlanSanitario();

        $response = $this->setUpRequest()->getJson(route('plan_sanitario.index'));

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json->whereType('planes_sanitario', 'array')
                ->has('planes_sanitario', $this->cantidad_PlanesSanitario)
                ->has(
                    'planes_sanitario.0',
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                    => $json->whereAllType([
                        'id' => 'integer',
                        'fecha_inicio' => 'string',
                        'fecha_fin' => 'string',
                        'prox_dosis' => 'string',
                        'vacuna' => 'string',
                        'vacunados' => 'integer',
                        'ganado_vacunado' => 'string',
                    ])
                )
        );
    }


    public function test_obtener_planes_sanitario_pendientes(): void
    {
        /* planes sanitarios en el cual su proxima dosis es menor a la fecha actual, por ende deben aplicarse */
        Plan_sanitario::factory()
            ->count(30)
            ->for($this->hacienda)
            ->create(['prox_dosis' => now()->subDays(random_int(10, 100)), 'vacuna_id' => 1]);

        /* planes sanitarios en el cual su proxima dosis es mayor a la fecha actual, por ende estan proximos a aplicarse */
        Plan_sanitario::factory()
            ->count(3)
            ->for($this->hacienda)
            ->create(['prox_dosis' => now()->addDays(random_int(10, 100))]);

        $response = $this->setUpRequest()->getJson(route('plan_sanitario.pendientes'));

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json->whereType('planes_sanitario', 'array')
                ->has(
                    'planes_sanitario',
                    //ya que se coloca una vacuna para planes sanitarios pendientes, solo debe haber un tipo de vacuna pendiente
                    1,
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                    => $json->whereAllType([
                        'id' => 'integer',
                        'fecha_inicio' => 'string',
                        'fecha_fin' => 'string',
                        'vacuna' => 'string',
                        'vacunados' => 'integer',
                        'prox_dosis' => 'string',
                        'ganado_vacunado' => 'string',
                    ])
                )
        );
    }



    public function test_creacion_plan_sanitario_vacuna_aplica_algunos(): void
    {

        /* ganado con estado fallecido */
        Ganado::factory()
            ->count(30)
            ->for($this->hacienda)
            ->hasAttached($this->estadoFallecido)
            ->create(['tipo_id' => 4]);

        /* ganado con estado vendido */
        Ganado::factory()
            ->count(30)
            ->for($this->hacienda)
            ->hasAttached($this->estadoVendido)
            ->create(['tipo_id' => 4]);

        $this->planSanitario['vacuna_id'] = 4;
        $response = $this->setUpRequest()->postJson(route('plan_sanitario.store'), $this->planSanitario);

        $response->assertStatus(201)->assertJson(
            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType([
                'plan_sanitario.id' => 'integer',
                'plan_sanitario.fecha_inicio' => 'string',
                'plan_sanitario.fecha_fin' => 'string',
                'plan_sanitario.vacuna' => 'string',
                'plan_sanitario.vacunados' => 'integer',
                'plan_sanitario.ganado_vacunado' => 'string',
            ])
                /* deberian haber haber 30 vacunados, ya que la vacuna que se esta aplicando
            es valida para todo el reba;o */
                ->where('plan_sanitario.vacunados', fn(int $vacunados): bool => $vacunados <= 30)
                ->where('plan_sanitario.ganado_vacunado', "Novillo,Adulta")


        );
    }

    public function test_obtener_plan_sanitario(): void
    {
        $PlanesSanitario = $this->generarPlanSanitario();
        $idRandom = random_int(0, $this->cantidad_PlanesSanitario - 1);
        $idPlanSanitario = $PlanesSanitario[$idRandom]->id;

        $response = $this->setUpRequest()->getJson(route('plan_sanitario.show', $idPlanSanitario));

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType([
                'plan_sanitario.id' => 'integer',
                'plan_sanitario.fecha_inicio' => 'string',
                'plan_sanitario.fecha_fin' => 'string',
                'plan_sanitario.vacuna' => 'string',
                'plan_sanitario.vacunados' => 'integer',
                'plan_sanitario.ganado_vacunado' => 'string'
            ])
        );
    }

    public function test_actualizar_plan_sanitario(): void
    {
        $planSanitario = $this->generarPlanSanitario();
        $idRandom = random_int(0, $this->cantidad_PlanesSanitario - 1);
        $idplanSanitarioEditar = $planSanitario[$idRandom]->id;

        $response = $this->setUpRequest()->putJson(route('plan_sanitario.update', $idplanSanitarioEditar), $this->planSanitario);

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json
                ->where('plan_sanitario.fecha_inicio', Carbon::parse($this->planSanitario['fecha_inicio'])->format('d-m-Y'))
                ->where('plan_sanitario.fecha_fin', Carbon::parse($this->planSanitario['fecha_fin'])->format('d-m-Y'))
                ->whereAllType([
                    'plan_sanitario.id' => 'integer',
                    'plan_sanitario.fecha_inicio' => 'string',
                    'plan_sanitario.fecha_fin' => 'string',
                    'plan_sanitario.vacuna' => 'string',
                    'plan_sanitario.vacunados' => 'integer',
                    'plan_sanitario.ganado_vacunado' => 'string',
                ])
                ->etc()
        );
    }


    public function test_eliminar_plan_sanitario(): void
    {
        $PlanesSanitario = $this->generarPlanSanitario();
        $idRandom = random_int(0, $this->cantidad_PlanesSanitario - 1);
        $idToDelete = $PlanesSanitario[$idRandom]->id;

        $response = $this->setUpRequest()->deleteJson(route('plan_sanitario.destroy', ['plan_sanitario'  => $idToDelete]));

        $response->assertStatus(200)->assertJson(['plan_sanitarioID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_plan_sanitario(array $planSanitario, array $errores): void
    {
        $response = $this->setUpRequest()->postJson(route('plan_sanitario.store'), $planSanitario);

        $response->assertStatus(422)->assertInvalid($errores);
    }
}
