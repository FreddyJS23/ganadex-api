<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Finca;
use App\Models\Ganado;
use App\Models\PajuelaToro;
use App\Models\Parto;
use App\Models\Personal;
use App\Models\Servicio;
use App\Models\Toro;
use App\Models\User;
use App\Models\UsuarioVeterinario;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class PartoTest extends TestCase
{
    use RefreshDatabase;

    private array $parto = [
        'observacion' => 'bien',
        'nombre' => 'test',
        'numero' => 33,
        'fecha' => '2020-10-02',

        'sexo' => 'H',
        'peso_nacimiento' => 33,


    ];

    private int $cantidad_parto = 10;

    private $user;
    private $ganadoServicioMonta;
    private $ganadoServicioInseminacion;
    private $toro;
    private $pajuelaToro;
    private $servicioMonta;
    private $servicioInseminacion;
    private $veterinario;
    private $obrero;
    private $estado;
    private $estadoSano;
    private $estadoVendido;
    private $estadoFallecido;
    private $numero_toro;
    private string $urlServicioMonta;
    private string $urlServicioInseminacion;
    private $finca;
    private $userVeterinario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->estado = Estado::all();
        $this->estadoSano = Estado::find(1);
        $this->estadoVendido = Estado::find(2);
        $this->estadoFallecido = Estado::find(5);

        $this->user
            = User::factory()->hasConfiguracion()->create();

            $this->user->assignRole('admin');

            $this->finca
            = Finca::factory()
            ->for($this->user)
            ->create();

        $this->ganadoServicioMonta
            = Ganado::factory()
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->finca)
            ->create();

            $this->ganadoServicioInseminacion
            = Ganado::factory()
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->finca)
            ->create();

        $this->toro = Toro::factory()
            ->for($this->finca)
            ->for(Ganado::factory()->for($this->finca)->create(['sexo' => 'M']))->create();

            $this->pajuelaToro = PajuelaToro::factory()
        ->for($this->finca)
        ->create();

        $this->veterinario
        = Personal::factory()
        ->for($this->finca)
        ->create(['cargo_id' => 2]);

        $this->obrero
        = Personal::factory()
        ->for($this->finca)
        ->create(['cargo_id' => 1]);

        $this->servicioMonta = Servicio::factory()
            ->for($this->ganadoServicioMonta)
            ->for($this->toro, 'servicioable')
            ->create(['personal_id' => $this->veterinario]);

            $this->servicioInseminacion = Servicio::factory()
            ->for($this->ganadoServicioInseminacion)
            ->for($this->pajuelaToro, 'servicioable')
            ->create(['personal_id' => $this->veterinario]);

            $this->userVeterinario
            = User::factory()
            ->create(['usuario' => 'veterinario']);

            $this->userVeterinario->assignRole('veterinario');

            UsuarioVeterinario::factory()
            ->for(Personal::factory()->for($this->finca)->create(['nombre'=>'usuarioVeterinario','cargo_id' => 2]), 'veterinario')
            ->create(['admin_id' => $this->user->id,
            'user_id' => $this->userVeterinario->id]);



        $this->urlServicioMonta = sprintf('api/ganado/%s/parto', $this->ganadoServicioMonta->id);

        $this->urlServicioInseminacion = sprintf('api/ganado/%s/parto', $this->ganadoServicioInseminacion->id);
    }

    private function generarpartosMonta(): Collection
    {
        return Parto::factory()
            ->count($this->cantidad_parto)
            ->for($this->ganadoServicioMonta)
            ->for(Ganado::factory()->for($this->finca)->hasAttached($this->estado), 'ganado_cria')
            ->for($this->toro, 'partoable')
            ->create(['personal_id' => $this->veterinario]);
    }

    private function generarpartosInseminacion(): Collection
    {
        return Parto::factory()
            ->count($this->cantidad_parto)
            ->for($this->ganadoServicioMonta)
            ->for(Ganado::factory()->for($this->finca)->hasAttached($this->estado), 'ganado_cria')
            ->for($this->pajuelaToro, 'partoable')
            ->create(['personal_id' => $this->veterinario]);
    }

    public static function ErrorInputProvider(): array
    {
        return [

            'caso de insertar datos erróneos' => [
                [
                    'observacion' => 'bi',
                    'nombre' => 'te',
                    'numero' => 'd3',
                    'sexo' => 'macho',
                    'peso_nacimiento' => '33mKG',
                ], ['observacion', 'nombre', 'numero', 'sexo', 'peso_nacimiento']
            ],
            'caso de no insertar datos requeridos' => [
                [], ['observacion', 'nombre', 'sexo']
            ],
            'caso de que exista el nombre o numero' => [
                [
                    'observacion',
                    'nombre' => 'test',
                    'numero' => 33,
                    'sexo' => 'H',
                    'tipo_id' => '4',
                    'peso_nacimiento' => 30,
                ], ['nombre', 'numero']
            ],
        ];
    }


    /**
     * A basic feature test example.
     */

    public function test_obtener_partos_monta(): void
    {

        $this->generarpartosMonta();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson($this->urlServicioMonta);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'partos',
                    $this->cantidad_parto,
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'observacion' => 'string',
                        'cria' => 'array',
                        'cria.id' => 'integer',
                        'cria.nombre' => 'string',
                        'cria.numero' => 'integer',
                        'cria.sexo' => 'string',
                        'cria.origen' => 'string',
                        'cria.fecha_nacimiento' => 'string',
                    ])->has(
                        'padre_toro',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'numero' => 'integer'])
                    )->has(
                        'personal',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                            ->where('cargo', 'veterinario')
                    )
                )
            );
    }


    public function test_creacion_parto_monta(): void
    {

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson($this->urlServicioMonta, $this->parto + ['personal_id' => $this->veterinario->id]);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'parto',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'observacion' => 'string',
                        'cria' => 'array',
                        'cria.id' => 'integer',
                        'cria.nombre' => 'string',
                        'cria.numero' => 'integer',
                        'cria.sexo' => 'string',
                        'cria.origen' => 'string',
                        'cria.fecha_nacimiento' => 'string',
                    ])->has(
                        'padre_toro',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'numero' => 'integer'])
                    )->has(
                        'personal',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                            ->where('cargo', 'veterinario')
                    )
                )
            );
    }

    public function test_creacion_parto_atiende_personal_obrero(): void
    {

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson($this->urlServicioMonta, $this->parto + ['personal_id' => $this->obrero->id]);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'parto',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'observacion' => 'string',
                        'cria' => 'array',
                        'cria.id' => 'integer',
                        'cria.nombre' => 'string',
                        'cria.numero' => 'integer',
                        'cria.sexo' => 'string',
                        'cria.origen' => 'string',
                        'cria.fecha_nacimiento' => 'string',
                    ])->has(
                        'padre_toro',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'numero' => 'integer'])
                    )->has(
                        'personal',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                            ->where('cargo', 'obrero')
                    )
                )
            );
    }

    public function test_creacion_parto_monta_usuario_veterinario(): void
    {

        $response = $this->actingAs($this->userVeterinario)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson($this->urlServicioMonta, $this->parto);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'parto',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'observacion' => 'string',
                        'cria' => 'array',
                        'cria.id' => 'integer',
                        'cria.nombre' => 'string',
                        'cria.numero' => 'integer',
                        'cria.sexo' => 'string',
                        'cria.origen' => 'string',
                        'cria.fecha_nacimiento' => 'string',
                    ])->has(
                        'padre_toro',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'numero' => 'integer'])
                    )->has(
                        'personal',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                        ->where('nombre', 'usuarioVeterinario')
                        ->where('cargo', 'veterinario')
                    )
                )
            );
    }

    public function test_error_creacion_parto_sin_servicio_previo(): void
    {

        $ganado = Ganado::factory()
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->finca)
            ->create();

            $this->urlServicioMonta = sprintf('api/ganado/%s/parto', $ganado->id);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson($this->urlServicioMonta, $this->parto + ['personal_id' => $this->veterinario->id]);

        $response->assertStatus(422)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->where('errors.servicio.0', 'Para registrar un parto la vaca debe de tener un servicio previo')
                ->etc()
            )
            ;

    }

    public function test_error_creacion_parto_sin_estado_gestacion(): void
    {

        $ganado = Ganado::factory()
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estadoSano)
            ->for($this->finca)
            ->create();

            $this->servicioMonta = Servicio::factory()
            ->for($ganado)
            ->for($this->toro, 'servicioable')
            ->create(['personal_id' => $this->veterinario]);


            $this->urlServicioMonta = sprintf('api/ganado/%s/parto', $ganado->id);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson($this->urlServicioMonta, $this->parto + ['personal_id' => $this->veterinario->id]);

        $response->assertStatus(422)
        ->assertJson(
            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->where('errors.estado_gestacion.0', 'Para registrar un parto la vaca debe estar en gestacion')
            ->etc()
        )
        ;

    }


    public function test_obtener_parto_monta(): void
    {
        $partos = $this->generarpartosMonta();

        $idRandom = random_int(0, $this->cantidad_parto - 1);
        $idparto = $partos[$idRandom]->id;
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf($this->urlServicioMonta . '/%s', $idparto));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'parto',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'observacion' => 'string',
                        'cria' => 'array',
                        'cria.id' => 'integer',
                        'cria.nombre' => 'string',
                        'cria.numero' => 'integer',
                        'cria.sexo' => 'string',
                        'cria.origen' => 'string',
                        'cria.fecha_nacimiento' => 'string',
                    ])->has(
                        'padre_toro',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'numero' => 'integer'])
                    )->has(
                        'personal',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                            ->where('cargo', 'veterinario')
                    )
                )
            );
    }
    public function test_actualizar_parto_monta(): void
    {
        $partos = $this->generarpartosMonta();
        $idRandom = random_int(0, $this->cantidad_parto - 1);
        $idpartoEditar = $partos[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(sprintf($this->urlServicioMonta . '/%s', $idpartoEditar), $this->parto + ['numero_toro' => $this->numero_toro]);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json
                ->where('parto.observacion', $this->parto['observacion'])
            ->has(
                'parto.personal',
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                    ->where('cargo', 'veterinario')
            )
                ->etc()
        );
    }


    public function test_eliminar_parto_monta(): void
    {
        $partos = $this->generarpartosInseminacion();
        $idRandom = random_int(0, $this->cantidad_parto - 1);
        $idToDelete = $partos[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->deleteJson(sprintf($this->urlServicioMonta . '/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['partoID' => $idToDelete]);
    }


    /* partos con inseminacion */

    public function test_obtener_partos_inseminacion(): void
    {

        $this->generarpartosInseminacion();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson($this->urlServicioMonta);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'partos',
                    $this->cantidad_parto,
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'observacion' => 'string',
                        'cria' => 'array',
                        'cria.id' => 'integer',
                        'cria.nombre' => 'string',
                        'cria.numero' => 'integer',
                        'cria.sexo' => 'string',
                        'cria.origen' => 'string',
                        'cria.fecha_nacimiento' => 'string',
                    ])->has(
                        'pajuela_toro',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'codigo' => 'string'])
                    )->has(
                        'personal',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                            ->where('cargo', 'veterinario')
                    )
                )
            );
    }


    public function test_creacion_parto_inseminacion(): void
    {

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson($this->urlServicioInseminacion, $this->parto + ['personal_id' => $this->veterinario->id]);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'parto',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'observacion' => 'string',
                        'cria' => 'array',
                        'cria.id' => 'integer',
                        'cria.nombre' => 'string',
                        'cria.numero' => 'integer',
                        'cria.sexo' => 'string',
                        'cria.origen' => 'string',
                        'cria.fecha_nacimiento' => 'string',
                    ])->has(
                        'pajuela_toro',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'codigo' => 'string'])
                    )->has(
                        'personal',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                            ->where('cargo', 'veterinario')
                    )
                )
            );
    }


    public function test_obtener_parto_inseminacion(): void
    {
        $partos = $this->generarpartosInseminacion();

        $idRandom = random_int(0, $this->cantidad_parto - 1);
        $idparto = $partos[$idRandom]->id;
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf($this->urlServicioInseminacion . '/%s', $idparto));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'parto',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'observacion' => 'string',
                        'cria' => 'array',
                        'cria.id' => 'integer',
                        'cria.nombre' => 'string',
                        'cria.numero' => 'integer',
                        'cria.sexo' => 'string',
                        'cria.origen' => 'string',
                        'cria.fecha_nacimiento' => 'string',
                    ])->has(
                        'pajuela_toro',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'codigo' => 'string'])
                    )->has(
                        'personal',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                            ->where('cargo', 'veterinario')
                    )
                )
            );
    }

    public function test_actualizar_parto_inseminacion(): void
    {
        $partos = $this->generarpartosInseminacion();
        $idRandom = random_int(0, $this->cantidad_parto - 1);
        $idpartoEditar = $partos[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(sprintf($this->urlServicioInseminacion . '/%s', $idpartoEditar), $this->parto + ['numero_toro' => $this->numero_toro]);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json
                ->where('parto.observacion', $this->parto['observacion'])
            ->has(
                'parto.personal',
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                    ->where('cargo', 'veterinario')
            )
                ->etc()
        );
    }


    public function test_eliminar_parto_inseminacion(): void
    {
        $partos = $this->generarpartosInseminacion();
        $idRandom = random_int(0, $this->cantidad_parto - 1);
        $idToDelete = $partos[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->deleteJson(sprintf($this->urlServicioInseminacion . '/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['partoID' => $idToDelete]);
    }


    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_parto(array $parto, array $errores): void
    {
        //crear personal no veterinario
        Personal::factory()
            ->for($this->finca)
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

        Ganado::factory()
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->finca)
            ->create(['nombre' => 'test', 'numero' => 33]);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson($this->urlServicioInseminacion, $parto);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_obtener_partos_de_todas_las_vacas(): void
    {
        /* partos con monta fallecidos */
        Ganado::factory()
            ->count(5)
            ->hasPeso(1)
            ->hasServicios(7, ['servicioable_id' => $this->toro->id,'servicioable_type' => $this->toro->getMorphClass(), 'personal_id' => $this->veterinario->id])
            ->hasParto(3, function (array $attributes, Ganado $ganado): array {
                $finca = $ganado->finca->id;
                $veterinario = Personal::factory()->create(['finca_id' => $finca, 'cargo_id' => 2]);
                $cria = Ganado::factory()->create(['finca_id' => $finca]);

                return ['partoable_id' => $ganado->servicioReciente->servicioable->id,'partoable_type' => $ganado->servicioReciente->servicioable->getMorphClass(), 'ganado_cria_id' => $cria->id, 'personal_id' => $veterinario->id];
            })
            ->hasEvento(1)
            ->hasAttached($this->estadoFallecido)
            ->for($this->finca)
            ->create();

            /* partos con monta fallecidos vendidos */
        Ganado::factory()
            ->count(5)
            ->hasPeso(1)
            ->hasServicios(7, ['servicioable_id' => $this->toro->id,'servicioable_type' => $this->toro->getMorphClass(), 'personal_id' => $this->veterinario->id])
            ->hasParto(3, function (array $attributes, Ganado $ganado): array {
                $finca = $ganado->finca->id;
                $veterinario = Personal::factory()->create(['finca_id' => $finca, 'cargo_id' => 2]);
                $cria = Ganado::factory()->create(['finca_id' => $finca]);

                return ['partoable_id' => $ganado->servicioReciente->servicioable->id,'partoable_type' => $ganado->servicioReciente->servicioable->getMorphClass(), 'ganado_cria_id' => $cria->id, 'personal_id' => $veterinario->id];
            })
            ->hasEvento(1)
            ->hasAttached($this->estadoVendido)
            ->for($this->finca)
            ->create();

            /* partos con monta fallecidos sanos */
        Ganado::factory()
            ->count(5)
            ->hasPeso(1)
            ->hasServicios(7, ['servicioable_id' => $this->toro->id,'servicioable_type' => $this->toro->getMorphClass(), 'personal_id' => $this->veterinario->id])
            ->hasParto(3, function (array $attributes, Ganado $ganado): array {
                $finca = $ganado->finca->id;
                $veterinario = Personal::factory()->create(['finca_id' => $finca, 'cargo_id' => 2]);
                $cria = Ganado::factory()->create(['finca_id' => $finca]);

                return ['partoable_id' => $ganado->servicioReciente->servicioable->id,'partoable_type' => $ganado->servicioReciente->servicioable->getMorphClass(), 'ganado_cria_id' => $cria->id, 'personal_id' => $veterinario->id];
            })
            ->hasEvento(1)
            ->hasAttached($this->estadoSano)
            ->for($this->finca)
            ->create();

            /* partos con inseminacion */
        Ganado::factory()
            ->count(5)
            ->hasPeso(1)
            ->hasServicios(7, ['servicioable_id' => $this->pajuelaToro->id,'servicioable_type' => $this->pajuelaToro->getMorphClass(), 'personal_id' => $this->veterinario->id])
            ->hasParto(3, function (array $attributes, Ganado $ganado): array {
                $finca = $ganado->finca->id;
                $veterinario = Personal::factory()->create(['finca_id' => $finca, 'cargo_id' => 2]);
                $cria = Ganado::factory()->create(['finca_id' => $finca]);

                return ['partoable_id' => $ganado->servicioReciente->servicioable->id,'partoable_type' => $ganado->servicioReciente->servicioable->getMorphClass(), 'ganado_cria_id' => $cria->id, 'personal_id' => $veterinario->id];
            })
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->finca)
            ->create();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('todosPartos'));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'todos_partos',10,
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType([
                        'id' => 'integer',
                        'numero' => 'integer',
                        'ultimo_parto' => 'string',
                        'total_partos' => 'integer'
                    ])->has(
                        'toro',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType([
                            'id' => 'integer',
                            'numero' => 'integer',
                        ])
                    )->has(
                        'cria',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType([
                            'id' => 'integer',
                            'numero' => 'integer',
                        ])
                    )
                )->has(
                    'todos_partos.6',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType([
                        'id' => 'integer',
                        'numero' => 'integer',
                        'ultimo_parto' => 'string',
                        'total_partos' => 'integer'
                    ])->has(
                        'pajuela_toro',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType([
                            'id' => 'integer',
                            'codigo' => 'string',
                        ])
                    )->has(
                        'cria',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                        => $json->whereAllType([
                            'id' => 'integer',
                            'numero' => 'integer',
                        ])
                    )
                )
            );
    }
}
