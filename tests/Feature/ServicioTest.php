<?php

namespace Tests\Feature;

use App\Models\Ganado;
use App\Models\PajuelaToro;
use App\Models\Parto;
use App\Models\PartoCria;
use App\Models\Personal;
use App\Models\Servicio;
use Illuminate\Database\Eloquent\Collection;
use Tests\Feature\Common\NeedsEstado;
use Tests\Feature\Common\NeedsGanado;
use Tests\Feature\Common\NeedsPersonal;
use Tests\Feature\Common\NeedsSetupRequest;
use Tests\Feature\Common\NeedsToro;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Support\Str;
use Tests\Feature\Common\NeedsUsuarioVeterinario;
use Tests\TestCase;

class ServicioTest extends TestCase
{
    use NeedsSetupRequest,
        NeedsGanado,
        NeedsEstado,
        NeedsToro,
        NeedsPersonal,
        NeedsUsuarioVeterinario {
        NeedsSetupRequest::setUp as needsSetupRequestSetUp;
        NeedsEstado::setUp as needsEstadoSetUp;
        NeedsToro::setUp as needsToroSetUp;
        NeedsPersonal::setUp as needsPersonalSetUp;
        NeedsUsuarioVeterinario::setUp as needsUsuarioVeterinarioSetUp;
    }

    private array $servicioMonta = [
        'observacion' => 'bien',
        'tipo' => 'monta',
        'fecha' => '2020-10-02',

    ];
    private array $servicioInseminacion = [
        'observacion' => 'bien',
        'fecha' => '2020-10-02',
        'tipo' => 'inseminacion'
    ];

    private int $cantidad_servicio = 10;


    private $pajuelaToro;
    private string $url;


    protected function setUp(): void
    {
        $this->needsSetupRequestSetUp();
        $this->needsEstadoSetUp();
        $this->needsPersonalSetUp();
        $this->needsToroSetUp();
        $this->generarGanado();

        $this->pajuelaToro = PajuelaToro::factory()->for($this->hacienda)->create();



        $this->url = sprintf('api/ganado/%s/servicio', $this->ganado->id);
    }

    private function generarServicios(bool $monta = true): Collection
    {
        $servicios = Servicio::factory()
            ->count($this->cantidad_servicio)
            ->for($this->ganado);

        if ($monta) {
            $servicios = $servicios->for($this->toro, 'servicioable');
        } else $servicios = $servicios->for($this->pajuelaToro, 'servicioable');

        return $servicios->create(['personal_id' => $this->veterinario]);
    }


    public static function ErrorInputProviderMonta(): array
    {
        return [

            'caso de insertar toro inexistente' => [
                [
                    'observacion' => 'bien',
                    'toro_id' => 0,
                    'tipo' => 'monta',
                    'personal_id' => 0
                ],
                ['toro_id', 'personal_id']
            ],
            'caso de insertar datos erróneos' => [
                [
                    'observacion' => 'te',
                    'toro_id' => 'hj',
                    'tipo' => 'nose',
                ],
                ['observacion', 'toro_id', 'tipo']
            ],
            'caso de no insertar datos requeridos' => [
                [],
                ['observacion', 'tipo']
            ],
            'caso de inseminacion, personal debe ser requerido' => [
                [
                    'observacion' => 'bien',
                    'toro_id' => 0,
                    'tipo' => 'inseminacion',
                ],
                ['personal_id', 'toro_id']
            ],
            'caso de monta, personal puede ser opcional' => [
                [
                    'observacion' => 'bien',
                    'toro_id' => 0,
                    'tipo' => 'monta',
                ],
                ['toro_id']
            ],

        ];
    }
    public static function ErrorInputProviderInseminacion(): array
    {
        return [

            'caso de insertar pajuela toro inexistente' => [
                [
                    'observacion' => 'bien',
                    'pajuela_toro_id' => 0,
                    'tipo' => 'monta',
                ],
                ['pajuela_toro_id']
            ],
            'caso de insertar datos erróneos' => [
                [
                    'observacion' => 'te',
                    'pajuela_toro_id' => 'hj',
                    'tipo' => 'nose',
                ],
                ['observacion', 'pajuela_toro_id', 'tipo']
            ],
            'caso de no insertar datos requeridos' => [
                [],
                ['observacion', 'tipo']
            ],
            'caso de insertar un personal que no sea veterinario' => [
                [
                    'personal_id' => 2
                ],
                ['personal_id']
            ],
        ];
    }

    /**
     * A basic feature test example.
     */

    public function test_obtener_servicios_monta(): void
    {
        $this->generarServicios(true);

        $response = $this->setUpRequest()->getJson($this->url);

        $response->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'servicios',
                    $this->cantidad_servicio,
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'observacion' => 'string',
                            'fecha' => 'string',
                        ])->where('tipo', fn(string $tipoServicio) => Str::contains($tipoServicio, ['Monta', 'Inseminacion']))
                        ->has(
                            'toro',
                            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                            => $json->whereAllType(['id' => 'integer', 'numero' => 'integer|null'])
                        )
                        ->has(
                            'veterinario',
                            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                            => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                        )
                )
            );
    }


    public function test_creacion_servicio_monta(): void
    {

        $response = $this->setUpRequest()->postJson($this->url, $this->servicioMonta + ['toro_id' => $this->toro->id, 'personal_id' => $this->veterinario->id]);

        $response->assertStatus(201)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'servicio',
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'observacion' => 'string',
                            'fecha' => 'string',
                        ])->where('tipo', fn(string $tipoServicio) => Str::contains($tipoServicio, ['Monta', 'Inseminacion']))
                        ->has(
                            'toro',
                            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                            => $json->whereAllType(['id' => 'integer', 'numero' => 'integer|null'])
                        )->where('veterinario', null)
                )
            );
    }


    public function test_error_creacion_servicio_a_una_vaca_con_estado_gestacion(): void
    {

        $ganado = Ganado::factory()
            ->hasEvento(['prox_revision' => null])
            ->hasAttached([$this->estadoGestacion])
            ->for($this->hacienda)
            ->create(['tipo_id' => 3]);

        $response = $this->setUpRequest()
        ->postJson(route('servicio.store', [$ganado->id]), $this->servicioMonta + ['toro_id' => $this->toro->id, 'personal_id' => $this->veterinario->id]);

        $response->assertStatus(422)->assertJson(['message' => 'La vaca esta en gestación, si ocurrió un aborto registre una revision con con el diagnostico de "aborto"']);
    }

    /* en caso de que que el ganado tenga muchos estados, por si hay colisiones con los demas estados */
    public function test_error_creacion_servicio_a_una_vaca_con_muchos_estados(): void
    {
        $ganado = Ganado::factory()
            ->hasEvento(['prox_revision' => null])
            ->hasAttached($this->estado)
            ->for($this->hacienda)
            ->create(['tipo_id' => 3]);

        $response = $this->setUpRequest()->postJson(route('servicio.store', [$ganado->id]), $this->servicioMonta + ['toro_id' => $this->toro->id, 'personal_id' => $this->veterinario->id]);

        $response->assertStatus(422)->assertJson(['message' => 'La vaca esta en gestación, si ocurrió un aborto registre una revision con con el diagnostico de "aborto"']);
    }


    public function test_creacion_servicio_monta_sin_veterinario(): void
    {

        $response = $this->setUpRequest()->postJson($this->url, $this->servicioMonta + ['toro_id' => $this->toro->id]);

        $response->assertStatus(201)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'servicio',
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'observacion' => 'string',
                            'fecha' => 'string',
                        ])->where('tipo', fn(string $tipoServicio) => Str::contains($tipoServicio, ['Monta', 'Inseminacion']))
                        ->has(
                            'toro',
                            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                            => $json->whereAllType(['id' => 'integer', 'numero' => 'integer|null'])
                        )
                        ->where('veterinario', null)
                )
            );
    }

    public function test_creacion_servicio_monta_usuario_veterinario(): void
    {

        $response = $this->actingAs($this->userVeterinario)->withSession(['hacienda_id' => $this->hacienda->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson($this->url, $this->servicioMonta + ['toro_id' => $this->toro->id]);

        $response->assertStatus(201)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'servicio',
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'observacion' => 'string',
                            'fecha' => 'string',
                        ])->where('tipo', fn(string $tipoServicio) => Str::contains($tipoServicio, ['Monta', 'Inseminacion']))
                        ->has(
                            'toro',
                            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                            => $json->whereAllType(['id' => 'integer', 'numero' => 'integer|null'])
                        )->where('veterinario', null)
                )
            );
    }


    public function test_obtener_servicio(): void
    {
        $servicios = $this->generarServicios(true);

        $idRandom = random_int(0, $this->cantidad_servicio - 1);
        $idservicio = $servicios[$idRandom]->id;
        $response = $this->setUpRequest()->getJson(sprintf($this->url . '/%s', $idservicio));

        $response->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'servicio',
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'observacion' => 'string',
                            'fecha' => 'string',
                        ])->where('tipo', fn(string $tipoServicio) => Str::contains($tipoServicio, ['Monta', 'Inseminacion']))
                        ->has(
                            'toro',
                            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                            => $json->whereAllType(['id' => 'integer', 'numero' => 'integer|null'])
                        )->has(
                            'veterinario',
                            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                            => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                        )
                )
            );
    }

    public function test_obtener_servicio_sin_veterinario(): void
    {

        $servicio = Servicio::factory()
            ->for($this->ganado)
            ->for($this->toro, 'servicioable')
            ->create();

        $response = $this->setUpRequest()->getJson(sprintf($this->url . '/%s', $servicio->id));

        $response->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'servicio',
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'observacion' => 'string',
                            'fecha' => 'string',
                        ])->where('tipo', fn(string $tipoServicio) => Str::contains($tipoServicio, ['Monta', 'Inseminacion']))
                        ->has(
                            'toro',
                            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                            => $json->whereAllType(['id' => 'integer', 'numero' => 'integer|null'])
                        )->where('veterinario', null)
                )
            );
    }

    public function test_obtener_servicio_con_veterinario(): void
    {
        $servicios = $this->generarServicios(true);

        $idRandom = random_int(0, $this->cantidad_servicio - 1);
        $idservicio = $servicios[$idRandom]->id;

        $response = $this->setUpRequest()->getJson(sprintf($this->url . '/%s', $idservicio));

        $response->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'servicio',
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'observacion' => 'string',
                            'fecha' => 'string',
                        ])->where('tipo', fn(string $tipoServicio) => Str::contains($tipoServicio, ['Monta', 'Inseminacion']))
                        ->has(
                            'toro',
                            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                            => $json->whereAllType(['id' => 'integer', 'numero' => 'integer|null'])
                        )->has(
                            'veterinario',
                            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                            => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                        )
                )
            );
    }

    public function test_actualizar_servicio_monta(): void
    {
        $servicios = $this->generarServicios(true);
        $idRandom = random_int(0, $this->cantidad_servicio - 1);
        $idservicioEditar = $servicios[$idRandom]->id;

        $response = $this->setUpRequest()->putJson(sprintf($this->url . '/%s', $idservicioEditar), $this->servicioMonta + ['toro_id' => $this->toro->id]);

        $response->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'servicio',
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->where('observacion', $this->servicioMonta['observacion'])
                        ->where('tipo', ucwords((string) $this->servicioMonta['tipo']))
                        ->has(
                            'veterinario',
                            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                            => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                        )
                        ->etc()
                )
            );
    }

    public function test_eliminar_servicio_monta(): void
    {
        $servicios = $this->generarServicios(true);
        $idRandom = random_int(0, $this->cantidad_servicio - 1);
        $idToDelete = $servicios[$idRandom]->id;


        $response = $this->setUpRequest()->deleteJson(sprintf($this->url . '/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['servicioID' => $idToDelete]);
    }



    /**
     * @dataProvider ErrorinputProviderMonta
     */
    public function test_error_validacion_registro_servicio_monta(array $servicio, array $errores): void
    {
        //crear personal no veterinario
        Personal::factory()
            ->for($this->user)->hasAttached($this->hacienda)
            ->create([
                'id' => 2,
                'ci' => 28472738,
                'nombre' => 'juan',
                'apellido' => 'perez',
                'fecha_nacimiento' => '2000-02-12',
                'telefono' => '0424-1234567',
                'cargo_id' => 1,
            ]);;

        $response = $this->setUpRequest()->postJson($this->url, $servicio);

        $response->assertStatus(422)->assertInvalid($errores);
    }



    /*servicio con inseminacion*/

    public function test_obtener_servicios_inseminacion(): void
    {
        $this->generarServicios(false);

        $response = $this->setUpRequest()->getJson($this->url);

        $response->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'servicios',
                    $this->cantidad_servicio,
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'observacion' => 'string',
                            'fecha' => 'string',
                        ])->where('tipo', fn(string $tipoServicio) => Str::contains($tipoServicio, ['Monta', 'Inseminacion']))
                        ->has(
                            'pajuela_toro',
                            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                            => $json->whereAllType(['id' => 'integer', 'codigo' => 'string'])
                        )
                        ->has(
                            'veterinario',
                            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                            => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                        )
                )
            );
    }


    public function test_creacion_servicio_inseminacion(): void
    {

        $response = $this->setUpRequest()
        ->postJson($this->url, $this->servicioInseminacion + ['pajuela_toro_id' => $this->pajuelaToro->id, 'personal_id' => $this->veterinario->id]);

        $response->assertStatus(201)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'servicio',
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'observacion' => 'string',
                            'fecha' => 'string',
                        ])->where('tipo', fn(string $tipoServicio) => Str::contains($tipoServicio, ['Monta', 'Inseminacion']))
                        ->has(
                            'pajuela_toro',
                            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                            => $json->whereAllType(['id' => 'integer', 'codigo' => 'string'])
                        )->has(
                            'veterinario',
                            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                            => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                        )
                )
            );
    }


    public function test_obtener_servicio_inseminacion(): void
    {
        $servicios = $this->generarServicios(false);

        $idRandom = random_int(0, $this->cantidad_servicio - 1);
        $idservicio = $servicios[$idRandom]->id;
        $response = $this->setUpRequest()->getJson(sprintf($this->url . '/%s', $idservicio));

        $response->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'servicio',
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'observacion' => 'string',
                            'fecha' => 'string',
                        ])->where('tipo', fn(string $tipoServicio) => Str::contains($tipoServicio, ['Monta', 'Inseminacion']))
                        ->has(
                            'pajuela_toro',
                            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                            => $json->whereAllType(['id' => 'integer', 'codigo' => 'string'])
                        )->has(
                            'veterinario',
                            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                            => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                        )
                )
            );
    }
    public function test_actualizar_servicio_inseminacion(): void
    {
        $servicios = $this->generarServicios(false);
        $idRandom = random_int(0, $this->cantidad_servicio - 1);
        $idservicioEditar = $servicios[$idRandom]->id;

        $response = $this->setUpRequest()->putJson(sprintf($this->url . '/%s', $idservicioEditar), $this->servicioInseminacion + ['pajuela_toro_id' => $this->pajuelaToro->id]);

        $response->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'servicio',
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->where('observacion', $this->servicioInseminacion['observacion'])
                        ->where('tipo', ucwords((string) $this->servicioInseminacion['tipo']))
                        ->has(
                            'veterinario',
                            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                            => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                        )
                        ->etc()
                )
            );
    }

    public function test_eliminar_servicio_inseminacion(): void
    {
        $servicios = $this->generarServicios(false);
        $idRandom = random_int(0, $this->cantidad_servicio - 1);
        $idToDelete = $servicios[$idRandom]->id;


        $response = $this->setUpRequest()->deleteJson(sprintf($this->url . '/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['servicioID' => $idToDelete]);
    }



    /**
     * @dataProvider ErrorinputProviderInseminacion
     */
    public function test_error_validacion_registro_servicio_inseminacion(array $servicio, array $errores): void
    {

        //crear personal no veterinario
        Personal::factory()
            ->for($this->user)->hasAttached($this->hacienda)
            ->create([
                'id' => 2,
                'ci' => 28472738,
                'nombre' => 'juan',
                'apellido' => 'perez',
                'fecha_nacimiento' => '2000-02-12',
                'telefono' => '0424-1234567',
                'cargo_id' => 1,
            ]);;

        $response = $this->setUpRequest()->postJson($this->url, $servicio);

        $response->assertStatus(422)->assertInvalid($errores);
    }



    public function test_obtener_servicios_de_todas_las_vacas(): void
    {
        /* partos con monta fallecidas */
        Ganado::factory()
            ->count(5)
            ->hasPeso(1)
            ->hasServicios(7, ['servicioable_id' => $this->toro->id, 'servicioable_type' => $this->toro->getMorphClass(), 'personal_id' => $this->veterinario->id])
            ->has(Parto::factory()->has(PartoCria::factory()->state(['ganado_id' => Ganado::factory()->for($this->hacienda)->hasAttached($this->estado)]))
                ->state(function (array $attributes, Ganado $ganado): array {
                    $hacienda = $ganado->hacienda;
                    $user = $ganado->hacienda->user->id;
                    $veterinario = Personal::factory()->hasAttached($hacienda)->create(['user_id' => $user, 'cargo_id' => 2]);

                    return ['partoable_id' => $ganado->servicioReciente->servicioable->id, 'partoable_type' => $ganado->servicioReciente->servicioable->getMorphClass(), 'personal_id' => $veterinario->id];
                }))
            ->hasEvento(1)
            ->hasAttached($this->estadoFallecido)
            ->for($this->hacienda)
            ->create();

        /* partos con monta vendida */
        Ganado::factory()
            ->count(5)
            ->hasPeso(1)
            ->hasServicios(7, ['servicioable_id' => $this->toro->id, 'servicioable_type' => $this->toro->getMorphClass(), 'personal_id' => $this->veterinario->id])
            ->has(Parto::factory()->has(PartoCria::factory()->state(['ganado_id' => Ganado::factory()->for($this->hacienda)->hasAttached($this->estado)]))
                ->state(function (array $attributes, Ganado $ganado): array {
                    $hacienda = $ganado->hacienda;
                    $user = $ganado->hacienda->user->id;
                    $veterinario = Personal::factory()->hasAttached($hacienda)->create(['user_id' => $user, 'cargo_id' => 2]);

                    return ['partoable_id' => $ganado->servicioReciente->servicioable->id, 'partoable_type' => $ganado->servicioReciente->servicioable->getMorphClass(), 'personal_id' => $veterinario->id];
                }))
            ->hasEvento(1)
            ->hasAttached($this->estadoVendido)
            ->for($this->hacienda)
            ->create();

        /* partos con monta sanas */
        Ganado::factory()
            ->count(5)
            ->hasPeso(1)
            ->hasServicios(7, ['servicioable_id' => $this->toro->id, 'servicioable_type' => $this->toro->getMorphClass(), 'personal_id' => $this->veterinario->id])
            ->has(Parto::factory()->has(PartoCria::factory()->state(['ganado_id' => Ganado::factory()->for($this->hacienda)->hasAttached($this->estado)]))
                ->state(function (array $attributes, Ganado $ganado): array {
                    $hacienda = $ganado->hacienda;
                    $user = $ganado->hacienda->user->id;
                    $veterinario = Personal::factory()->hasAttached($hacienda)->create(['user_id' => $user, 'cargo_id' => 2]);

                    return ['partoable_id' => $ganado->servicioReciente->servicioable->id, 'partoable_type' => $ganado->servicioReciente->servicioable->getMorphClass(), 'personal_id' => $veterinario->id];
                }))
            ->hasEvento(1)
            ->hasAttached($this->estadoSano)
            ->for($this->hacienda)
            ->create();

        /* partos con inseminacion */
        Ganado::factory()
            ->count(5)
            ->hasPeso(1)
            ->hasServicios(7, ['servicioable_id' => $this->pajuelaToro->id, 'servicioable_type' => $this->pajuelaToro->getMorphClass(), 'personal_id' => $this->veterinario->id])
            ->has(Parto::factory()->has(PartoCria::factory()->state(['ganado_id' => Ganado::factory()->for($this->hacienda)->hasAttached($this->estado)]))
                ->state(function (array $attributes, Ganado $ganado): array {
                    $hacienda = $ganado->hacienda;
                    $user = $ganado->hacienda->user->id;
                    $veterinario = Personal::factory()->hasAttached($hacienda)->create(['user_id' => $user, 'cargo_id' => 2]);

                    return ['partoable_id' => $ganado->servicioReciente->servicioable->id, 'partoable_type' => $ganado->servicioReciente->servicioable->getMorphClass(), 'personal_id' => $veterinario->id];
                }))
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->hacienda)
            ->create();

        /* partos con monta sanas y estado pendiente servicio*/
        Ganado::factory()
            ->count(5)
            ->hasPeso(1)
            ->hasServicios(1, ['servicioable_id' => $this->toro->id, 'servicioable_type' => $this->toro->getMorphClass(), 'personal_id' => $this->veterinario->id])
            ->has(Parto::factory()->has(PartoCria::factory()->state(['ganado_id' => Ganado::factory()->for($this->hacienda)->hasAttached($this->estado)]))
                ->state(function (array $attributes, Ganado $ganado): array {
                    $hacienda = $ganado->hacienda;
                    $user = $ganado->hacienda->user->id;
                    $veterinario = Personal::factory()->hasAttached($hacienda)->create(['user_id' => $user, 'cargo_id' => 2]);

                    return ['partoable_id' => $ganado->servicioReciente->servicioable->id, 'partoable_type' => $ganado->servicioReciente->servicioable->getMorphClass(), 'personal_id' => $veterinario->id];
                }))
            ->hasEvento(1)
            ->hasAttached([$this->estadoSano, $this->estadoPendienteServicio],)
            ->for($this->hacienda)
            ->create();

        $response = $this->setUpRequest()->getJson(route('todasServicios'));

        $response->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                //son 26, ya que se esta contando la vaca que se crea en setUp
                $json->has('todos_servicios', 26)
                    ->has(
                        'todos_servicios.3',
                        fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType([
                            'id' => 'integer',
                            'numero' => 'integer|null',
                            'ultimo_servicio' => 'string',
                            'efectividad' => 'double|integer|null',
                            'toro' => 'array|null',
                            'total_servicios' => 'integer',
                            'pendiente' => 'boolean',
                        ])
                            ->where('estado', fn(string $estado) => Str::contains($estado, ['Sano', 'Fallecido', 'Vendido']))
                    )
                    ->has(
                        'todos_servicios.8',
                        fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType([
                            'pajuela_toro' => 'array|null',
                        ])
                            ->where('estado', fn(string $estado) => Str::contains($estado, ['Sano', 'Fallecido', 'Vendido']))
                            ->etc()
                    )
            );
    }
}
