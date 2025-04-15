<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Hacienda;
use App\Models\Ganado;
use App\Models\PajuelaToro;
use App\Models\Parto;
use App\Models\PartoCria;
use App\Models\Personal;
use App\Models\Servicio;
use App\Models\Toro;
use App\Models\User;
use App\Models\UsuarioVeterinario;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Support\Str;
use Tests\TestCase;

class ServicioTest extends TestCase
{
    use RefreshDatabase;

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

    private $user;
    private $ganado;
    private $veterinario;
    private $estado;
    private $estadoSano;
    private $estadoVendido;
    private $estadoFallecido;
    private $estadoPendienteServicio;
    private $userVeterinario;
    private $toro;
    private $pajuelaToro ;
    private string $url;
    private $hacienda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->hasConfiguracion()->create();

            $this->user->assignRole('admin');

        $this->estadoSano = Estado::find(1);
        $this->estadoVendido = Estado::find(2);
        $this->estadoFallecido = Estado::find(5);
        $this->estadoPendienteServicio=Estado::find(7);

            $this->hacienda
            = Hacienda::factory()
            ->for($this->user)
            ->create();

        $this->estado = Estado::where('estado','sano')->get();

        $this->veterinario
        = Personal::factory()
            ->for($this->user)->hasAttached($this->hacienda)
            ->create(['cargo_id' => 2]);

        $this->ganado
            = Ganado::factory()
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->hacienda)
            ->create();

        $this->toro = Toro::factory()
            ->for($this->hacienda)
            ->for(Ganado::factory()->for($this->hacienda)->create(['sexo' => 'M']))->create();

        $this->pajuelaToro = PajuelaToro::factory()->for($this->hacienda)->create();

        $this->userVeterinario
        = User::factory()
        ->create(['usuario' => 'veterinario']);

        $this->userVeterinario->assignRole('veterinario');

        UsuarioVeterinario::factory()
        ->for(Personal::factory()->for($this->user)->create(['nombre'=>'usuarioVeterinario','cargo_id' => 2]), 'veterinario')
        ->create(['admin_id' => $this->user->id,
        'user_id' => $this->userVeterinario->id]);


        $this->url = sprintf('api/ganado/%s/servicio', $this->ganado->id);
    }

    private function generarServicioMonta(): Collection
    {
        return Servicio::factory()
            ->count($this->cantidad_servicio)
            ->for($this->ganado)
            ->for($this->toro, 'servicioable')
            ->create(['personal_id' => $this->veterinario]);
    }

    private function generarServicioInseminacion(): Collection
    {
        return Servicio::factory()
            ->count($this->cantidad_servicio)
            ->for($this->ganado)
            ->for($this->pajuelaToro, 'servicioable')
            ->create(['personal_id' => $this->veterinario]);
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
                ], ['toro_id','personal_id']
            ],
            'caso de insertar datos erróneos' => [
                [
                    'observacion' => 'te',
                    'toro_id' => 'hj',
                    'tipo' => 'nose',
                ], ['observacion', 'toro_id', 'tipo']
            ],
            'caso de no insertar datos requeridos' => [
                [], ['observacion', 'tipo']
            ],
            'caso de inseminacion, personal debe ser requerido' => [
                [
                    'observacion' => 'bien',
                    'toro_id' => 0,
                    'tipo' => 'inseminacion',
                ], ['personal_id', 'toro_id']
            ],
            'caso de monta, personal puede ser opcional' => [
                [
                    'observacion' => 'bien',
                    'toro_id' => 0,
                    'tipo' => 'monta',
                ], ['toro_id']
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
                ], ['pajuela_toro_id']
            ],
            'caso de insertar datos erróneos' => [
                [
                    'observacion' => 'te',
                    'pajuela_toro_id' => 'hj',
                    'tipo' => 'nose',
                ], ['observacion', 'pajuela_toro_id', 'tipo']
            ],
            'caso de no insertar datos requeridos' => [
                [], ['observacion', 'tipo']
            ],
            'caso de insertar un personal que no sea veterinario' => [
                [
                    'personal_id' => 2
                ], ['personal_id']
            ],
        ];
    }

    /**
     * A basic feature test example.
     */

    public function test_obtener_servicios_monta(): void
    {
        $this->generarServicioMonta();

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson($this->url);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'servicios',
                    $this->cantidad_servicio,
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'observacion' => 'string',
                            'fecha' => 'string',
                        ])->where('tipo', fn (string $tipoServicio)=> Str::contains($tipoServicio, ['Monta', 'Inseminacion']))
                    ->has(
                        'toro',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'numero' => 'integer'])
                    )
                    ->has(
                        'veterinario',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                    )
                )
            );
    }


    public function test_creacion_servicio_monta(): void
    {

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson($this->url, $this->servicioMonta + ['toro_id' => $this->toro->id,'personal_id' => $this->veterinario->id]);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'servicio',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'observacion' => 'string',
                            'fecha' => 'string',
                        ])->where('tipo', fn (string $tipoServicio) => Str::contains($tipoServicio, ['Monta', 'Inseminacion']))
                    ->has(
                        'toro',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'numero' => 'integer'])
                    )->where('veterinario',null)
                )
            );
    }


    public function test_error_creacion_servicio_a_una_vaca_con_estado_gestacion(): void
    {
        $estadoGestacion = Estado::firstWhere('estado', 'gestacion');

        $ganado=Ganado::factory()
        ->hasEvento(['prox_revision' => null])
        ->hasAttached([ $estadoGestacion])
        ->for($this->hacienda)
        ->create(['tipo_id' => 3]);

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(route('servicio.store',[$ganado->id]), $this->servicioMonta + ['toro_id' => $this->toro->id,'personal_id' => $this->veterinario->id]);

        $response->assertStatus(422)->assertJson(['message' => 'La vaca esta en gestación, si ocurrió un aborto registre una revision con con el diagnostico de "aborto"']);

    }

/* en caso de que que el ganado tenga muchos estados, por si hay colisiones con los demas estados */
    public function test_error_creacion_servicio_a_una_vaca_con_muchos_estados(): void
    {
        $estados = Estado::all();

        $ganado=Ganado::factory()
        ->hasEvento(['prox_revision' => null])
        ->hasAttached($estados)
        ->for($this->hacienda)
        ->create(['tipo_id' => 3]);

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(route('servicio.store',[$ganado->id]), $this->servicioMonta + ['toro_id' => $this->toro->id,'personal_id' => $this->veterinario->id]);

        $response->assertStatus(422)->assertJson(['message' => 'La vaca esta en gestación, si ocurrió un aborto registre una revision con con el diagnostico de "aborto"']);

    }


    public function test_creacion_servicio_monta_sin_veterinario(): void
    {

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson($this->url, $this->servicioMonta + ['toro_id' => $this->toro->id]);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'servicio',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'observacion' => 'string',
                            'fecha' => 'string',
                        ])->where('tipo', fn (string $tipoServicio) => Str::contains($tipoServicio, ['Monta', 'Inseminacion']))
                    ->has(
                        'toro',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'numero' => 'integer'])
                    )
                    ->where('veterinario',null)
                )
            );
    }

    public function test_creacion_servicio_monta_usuario_veterinario(): void
    {

        $response = $this->actingAs($this->userVeterinario)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson($this->url, $this->servicioMonta + ['toro_id' => $this->toro->id]);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'servicio',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'observacion' => 'string',
                            'fecha' => 'string',
                        ])->where('tipo', fn (string $tipoServicio) => Str::contains($tipoServicio, ['Monta', 'Inseminacion']))
                    ->has(
                        'toro',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'numero' => 'integer'])
                    )->where('veterinario',null)
                )
            );
    }


    public function test_obtener_servicio(): void
    {
        $servicios = $this->generarServicioMonta();

        $idRandom = random_int(0, $this->cantidad_servicio - 1);
        $idservicio = $servicios[$idRandom]->id;
        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf($this->url . '/%s', $idservicio));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'servicio',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'observacion' => 'string',
                            'fecha' => 'string',
                        ])->where('tipo', fn (string $tipoServicio) => Str::contains($tipoServicio, ['Monta', 'Inseminacion']))
                    ->has(
                        'toro',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'numero' => 'integer'])
                    )->has(
                        'veterinario',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                    )
                )
            );
    }

    public function test_obtener_servicio_sin_veterinario(): void
    {

        $servicio=Servicio::factory()
            ->for($this->ganado)
            ->for($this->toro, 'servicioable')
            ->create();

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf($this->url . '/%s', $servicio->id));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'servicio',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'observacion' => 'string',
                            'fecha' => 'string',
                        ])->where('tipo', fn (string $tipoServicio) => Str::contains($tipoServicio, ['Monta', 'Inseminacion']))
                    ->has(
                        'toro',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'numero' => 'integer'])
                    )->where('veterinario',null)
                )
            );
    }

    public function test_obtener_servicio_con_veterinario(): void
    {
        $servicios = $this->generarServicioMonta();

        $idRandom = random_int(0, $this->cantidad_servicio - 1);
        $idservicio = $servicios[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf($this->url . '/%s', $idservicio));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'servicio',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'observacion' => 'string',
                            'fecha' => 'string',
                        ])->where('tipo', fn (string $tipoServicio) => Str::contains($tipoServicio, ['Monta', 'Inseminacion']))
                    ->has(
                        'toro',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'numero' => 'integer'])
                    )->has(
                        'veterinario',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                    )
                )
            );
    }

    public function test_actualizar_servicio_monta(): void
    {
        $servicios = $this->generarServicioMonta();
        $idRandom = random_int(0, $this->cantidad_servicio - 1);
        $idservicioEditar = $servicios[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(sprintf($this->url . '/%s', $idservicioEditar), $this->servicioMonta + ['toro_id' => $this->toro->id]);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'servicio',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->where('observacion', $this->servicioMonta['observacion'])
                    ->where('tipo', ucwords((string) $this->servicioMonta['tipo']))
                    ->has(
                        'veterinario',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                    )
                    ->etc()
                )
            );
    }

    public function test_eliminar_servicio_monta(): void
    {
        $servicios = $this->generarServicioMonta();
        $idRandom = random_int(0, $this->cantidad_servicio - 1);
        $idToDelete = $servicios[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->deleteJson(sprintf($this->url . '/%s', $idToDelete));

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
            ]);
        ;

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson($this->url, $servicio);

        $response->assertStatus(422)->assertInvalid($errores);
    }



    /*servicio con inseminacion*/

    public function test_obtener_servicios_inseminacion(): void
    {
        $this->generarServicioInseminacion();

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson($this->url);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'servicios',
                    $this->cantidad_servicio,
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'observacion' => 'string',
                            'fecha' => 'string',
                        ])->where('tipo', fn (string $tipoServicio)=> Str::contains($tipoServicio, ['Monta', 'Inseminacion']))
                    ->has(
                        'pajuela_toro',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'codigo' => 'string'])
                    )
                    ->has(
                        'veterinario',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                    )
                )
            );
    }


    public function test_creacion_servicio_inseminacion(): void
    {

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson($this->url, $this->servicioInseminacion + ['pajuela_toro_id' => $this->pajuelaToro->id,'personal_id' => $this->veterinario->id]);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'servicio',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'observacion' => 'string',
                            'fecha' => 'string',
                        ])->where('tipo', fn (string $tipoServicio) => Str::contains($tipoServicio, ['Monta', 'Inseminacion']))
                    ->has(
                        'pajuela_toro',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'codigo' => 'string'])
                    )->has(
                        'veterinario',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                    )
                )
            );
    }


    public function test_obtener_servicio_inseminacion(): void
    {
        $servicios = $this->generarServicioInseminacion();

        $idRandom = random_int(0, $this->cantidad_servicio - 1);
        $idservicio = $servicios[$idRandom]->id;
        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf($this->url . '/%s', $idservicio));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'servicio',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'observacion' => 'string',
                            'fecha' => 'string',
                        ])->where('tipo', fn (string $tipoServicio) => Str::contains($tipoServicio, ['Monta', 'Inseminacion']))
                    ->has(
                        'pajuela_toro',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'codigo' => 'string'])
                    )->has(
                        'veterinario',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                    )
                )
            );
    }
    public function test_actualizar_servicio_inseminacion(): void
    {
        $servicios = $this->generarServicioInseminacion();
        $idRandom = random_int(0, $this->cantidad_servicio - 1);
        $idservicioEditar = $servicios[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(sprintf($this->url . '/%s', $idservicioEditar), $this->servicioInseminacion + ['pajuela_toro_id' => $this->pajuelaToro->id]);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'servicio',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->where('observacion', $this->servicioInseminacion['observacion'])
                    ->where('tipo', ucwords((string) $this->servicioInseminacion['tipo']))
                    ->has(
                        'veterinario',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                    )
                    ->etc()
                )
            );
    }

    public function test_eliminar_servicio_inseminacion(): void
    {
        $servicios = $this->generarServicioInseminacion();
        $idRandom = random_int(0, $this->cantidad_servicio - 1);
        $idToDelete = $servicios[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->deleteJson(sprintf($this->url . '/%s', $idToDelete));

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
            ]);
        ;

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson($this->url, $servicio);

        $response->assertStatus(422)->assertInvalid($errores);
    }



    public function test_obtener_servicios_de_todas_las_vacas(): void
    {
        //eliminar estados previos de los ganados que se generan en el setUp
        DB::table('estado_ganado')->truncate();

        /* partos con monta fallecidas */
        Ganado::factory()
            ->count(5)
            ->hasPeso(1)
            ->hasServicios(7, ['servicioable_id' => $this->toro->id, 'servicioable_type' => $this->toro->getMorphClass(), 'personal_id' => $this->veterinario->id])
            ->has(Parto::factory()->has(PartoCria::factory()->state(['ganado_id'=>Ganado::factory()->for($this->hacienda)->hasAttached($this->estado)]))
            ->state(function (array $attributes, Ganado $ganado): array {
                $hacienda = $ganado->hacienda;
                $user=$ganado->hacienda->user->id;
                $veterinario = Personal::factory()->hasAttached($hacienda)->create(['user_id'=>$user,'cargo_id' => 2]);

                return ['partoable_id' => $ganado->servicioReciente->servicioable->id,'partoable_type' => $ganado->servicioReciente->servicioable->getMorphClass(), 'personal_id' => $veterinario->id];
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
            ->has(Parto::factory()->has(PartoCria::factory()->state(['ganado_id'=>Ganado::factory()->for($this->hacienda)->hasAttached($this->estado)]))
            ->state(function (array $attributes, Ganado $ganado): array {
                $hacienda = $ganado->hacienda;
                $user=$ganado->hacienda->user->id;
                $veterinario = Personal::factory()->hasAttached($hacienda)->create(['user_id'=>$user,'cargo_id' => 2]);

                return ['partoable_id' => $ganado->servicioReciente->servicioable->id,'partoable_type' => $ganado->servicioReciente->servicioable->getMorphClass(), 'personal_id' => $veterinario->id];
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
            ->has(Parto::factory()->has(PartoCria::factory()->state(['ganado_id'=>Ganado::factory()->for($this->hacienda)->hasAttached($this->estado)]))
            ->state(function (array $attributes, Ganado $ganado): array {
                $hacienda = $ganado->hacienda;
                $user=$ganado->hacienda->user->id;
                $veterinario = Personal::factory()->hasAttached($hacienda)->create(['user_id'=>$user,'cargo_id' => 2]);

                return ['partoable_id' => $ganado->servicioReciente->servicioable->id,'partoable_type' => $ganado->servicioReciente->servicioable->getMorphClass(), 'personal_id' => $veterinario->id];
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
            ->has(Parto::factory()->has(PartoCria::factory()->state(['ganado_id'=>Ganado::factory()->for($this->hacienda)->hasAttached($this->estado)]))
            ->state(function (array $attributes, Ganado $ganado): array {
                $hacienda = $ganado->hacienda;
                $user=$ganado->hacienda->user->id;
                $veterinario = Personal::factory()->hasAttached($hacienda)->create(['user_id'=>$user,'cargo_id' => 2]);

                return ['partoable_id' => $ganado->servicioReciente->servicioable->id,'partoable_type' => $ganado->servicioReciente->servicioable->getMorphClass(), 'personal_id' => $veterinario->id];
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
        ->has(Parto::factory()->has(PartoCria::factory()->state(['ganado_id'=>Ganado::factory()->for($this->hacienda)->hasAttached($this->estado)]))
        ->state(function (array $attributes, Ganado $ganado): array {
            $hacienda = $ganado->hacienda;
            $user=$ganado->hacienda->user->id;
            $veterinario = Personal::factory()->hasAttached($hacienda)->create(['user_id'=>$user,'cargo_id' => 2]);

            return ['partoable_id' => $ganado->servicioReciente->servicioable->id,'partoable_type' => $ganado->servicioReciente->servicioable->getMorphClass(), 'personal_id' => $veterinario->id];
        }))
        ->hasEvento(1)
        ->hasAttached([$this->estadoSano,$this->estadoPendienteServicio],)
        ->for($this->hacienda)
        ->create();

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('todasServicios'));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has('todos_servicios',25, fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType([
                    'id' => 'integer',
                    'numero' => 'integer',
                    'ultimo_servicio' => 'string',
                    'pajuela_toro' => 'array',
                    'efectividad' => 'double|integer|null',
                    'total_servicios' => 'integer',
                    'pendiente'=>'boolean',
                    'estado'=>'string',
                ]))
                //comprabar que vienen primero las sanas
                ->has('todos_servicios.2', fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->where('estado','sano')->etc())
                ->has('todos_servicios.1', fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType([
                'id' => 'integer',
                'numero' => 'integer',
                'ultimo_servicio' => 'string',
                'toro' => 'array',
                'efectividad' => 'double|integer|null',
                'total_servicios' => 'integer',
                'pendiente'=>'boolean',
                'estado'=>'string'
                ]))
                //vaca con estado pendiente de servicio
                ->has('todos_servicios.13', fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->where('pendiente',true)->etc())
            );
    }
}
