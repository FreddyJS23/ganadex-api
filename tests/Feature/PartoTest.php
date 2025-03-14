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
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class PartoTest extends TestCase
{
    use RefreshDatabase;

    private array $parto = [
        'observacion' => 'bien',
        'fecha' => '2020-10-02',
        'crias'=>[
            [
            'observacion' => 'bien',
            'nombre' => 'test',
            'numero' => 33,
            'sexo' => 'H',
            'peso_nacimiento' => 33
            ]
        ]
    ];

    private array $partoMorochos = [
        'observacion' => 'morochos',
        'fecha' => '2020-10-02',
        'crias'=>[
            [
            'observacion' => 'bien',
            'nombre' => 'cria1',
            'numero' => 33,
            'sexo' => 'H',
            'peso_nacimiento' => 33
            ],
            [
            'observacion' => 'regular',
            'nombre' => 'cria2',
            'numero' => 34,
            'sexo' => 'M',
            'peso_nacimiento' => 33
            ]
        ]
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
    private $hacienda;
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

            $this->hacienda
            = Hacienda::factory()
            ->for($this->user)
            ->create();

        $this->ganadoServicioMonta
            = Ganado::factory()
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->hacienda)
            ->create();

            $this->ganadoServicioInseminacion
            = Ganado::factory()
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->hacienda)
            ->create();

        $this->toro = Toro::factory()
            ->for($this->hacienda)
            ->for(Ganado::factory()->for($this->hacienda)->create(['sexo' => 'M']))->create();

            $this->pajuelaToro = PajuelaToro::factory()
        ->for($this->hacienda)
        ->create();

        $this->veterinario
        = Personal::factory()
        ->for($this->user)
        ->hasAttached($this->hacienda)
        ->create(['cargo_id' => 2]);

        $this->obrero
        = Personal::factory()
        ->for($this->user)
        ->hasAttached($this->hacienda)
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
            ->for(Personal::factory()->hasAttached($this->hacienda)->for($this->user)->create(['nombre'=>'usuarioVeterinario','cargo_id' => 2]), 'veterinario')
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
            ->has(PartoCria::factory()->for(Ganado::factory()->for($this->hacienda)->hasAttached($this->estado)))
            ->for($this->toro, 'partoable')
            ->create(['personal_id' => $this->veterinario]);
    }

    private function generarpartosInseminacion(): Collection
    {
        return Parto::factory()
            ->count($this->cantidad_parto)
            ->for($this->ganadoServicioMonta)
            //se usa el state en lugar de for para asegurarse de que cada parto tenga una cria distinta, con for una misma cria pertenececira a todos los partos
            ->has(PartoCria::factory()->state(['ganado_id'=>Ganado::factory()->for($this->hacienda)->hasAttached($this->estado)]))
            ->for($this->pajuelaToro, 'partoable')
            ->create(['personal_id' => $this->veterinario]);
    }

    public static function ErrorInputProvider(): array
    {
        return [

            'caso de insertar datos erróneos' => [
                [
                    'observacion' => 'bi',
                    'personal_id' => 'be',
                    'crias'=>[
                        [
                    'observacion' => 'bi',
                    'nombre' => 'te',
                    'numero' => 'd3',
                    'sexo' => 'macho',
                    'peso_nacimiento' => '33mKG']
                    ]
                ], ['observacion', 'crias.0.nombre','crias.0.numero', 'crias.0.sexo', 'crias.0.peso_nacimiento','crias.0.observacion']
            ],
            'caso de no insertar datos requeridos' => [
                [], ['observacion', 'crias','personal_id']
            ],
            'caso de que exista el nombre o numero' => [
                [
                    'observacion',
                    'crias'=>[
                        [
                        'nombre' => 'test',
                        'numero' => 33,
                        'sexo' => 'H',
                        'tipo_id' => '4',
                        'peso_nacimiento' => 30,
                    ]
                    ]
                ], ['crias.0.nombre', 'crias.0.numero']
            ],
            'caso de registrar morochos y se repitan los campos' => [
                [
                    'observacion',
                    'crias'=>[
                        [
                        'nombre' => 'morocho',
                        'numero' => 986,
                        'sexo' => 'H',
                        'tipo_id' => '4',
                        'peso_nacimiento' => 30,
                        ],
                        [
                        'nombre' => 'morocho',
                        'numero' => 986,
                        'sexo' => 'H',
                        'tipo_id' => '4',
                        'peso_nacimiento' => 30,
                        ],
                    ]
                ], ['crias.0.nombre', 'crias.0.numero','crias.1.nombre', 'crias.1.numero']
            ]
        ];
    }


    /**
     * A basic feature test example.
     */

    public function test_obtener_partos_monta(): void
    {

        $this->generarpartosMonta();

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson($this->urlServicioMonta);

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
                        'crias' => 'array',
                        'crias.0.id' => 'integer',
                        'crias.0.nombre' => 'string',
                        'crias.0.numero' => 'integer',
                        'crias.0.sexo' => 'string',
                        'crias.0.origen' => 'string',
                        'crias.0.fecha_nacimiento' => 'string',
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

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson($this->urlServicioMonta, $this->parto + ['personal_id' => $this->veterinario->id]);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'parto',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'observacion' => 'string',
                        'crias' => 'array',
                        'crias.0.id' => 'integer',
                        'crias.0.nombre' => 'string',
                        'crias.0.numero' => 'integer',
                        'crias.0.sexo' => 'string',
                        'crias.0.origen' => 'string',
                        'crias.0.fecha_nacimiento' => 'string',
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

    //en el parto hubieron dos crias
    public function test_creacion_parto_morochos_monta(): void
    {

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson($this->urlServicioMonta, $this->partoMorochos + ['personal_id' => $this->veterinario->id]);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'parto',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'observacion' => 'string',
                        'crias' => 'array',
                        'crias.0.id' => 'integer',
                        'crias.0.nombre' => 'string',
                        'crias.0.numero' => 'integer',
                        'crias.0.sexo' => 'string',
                        'crias.0.origen' => 'string',
                        'crias.0.fecha_nacimiento' => 'string',
                    ])
                    ->where('crias.0.observacion', $this->partoMorochos['crias'][0]['observacion'])
                    ->where('crias.0.nombre', $this->partoMorochos['crias'][0]['nombre'])
                    ->where('crias.1.observacion', $this->partoMorochos['crias'][1]['observacion'])
                    ->where('crias.1.nombre', $this->partoMorochos['crias'][1]['nombre'])
                    ->has(
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

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson($this->urlServicioMonta, $this->parto + ['personal_id' => $this->obrero->id]);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'parto',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'observacion' => 'string',
                        'crias' => 'array',
                        'crias.0.id' => 'integer',
                        'crias.0.nombre' => 'string',
                        'crias.0.numero' => 'integer',
                        'crias.0.sexo' => 'string',
                        'crias.0.origen' => 'string',
                        'crias.0.fecha_nacimiento' => 'string',
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

        $response = $this->actingAs($this->userVeterinario)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson($this->urlServicioMonta, $this->parto);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'parto',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'observacion' => 'string',
                        'crias' => 'array',
                        'crias.0.id' => 'integer',
                        'crias.0.nombre' => 'string',
                        'crias.0.numero' => 'integer',
                        'crias.0.sexo' => 'string',
                        'crias.0.origen' => 'string',
                        'crias.0.fecha_nacimiento' => 'string',
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
            ->for($this->hacienda)
            ->create();

            $this->urlServicioMonta = sprintf('api/ganado/%s/parto', $ganado->id);

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson($this->urlServicioMonta, $this->parto + ['personal_id' => $this->veterinario->id]);

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
            ->for($this->hacienda)
            ->create();

            $this->servicioMonta = Servicio::factory()
            ->for($ganado)
            ->for($this->toro, 'servicioable')
            ->create(['personal_id' => $this->veterinario]);


            $this->urlServicioMonta = sprintf('api/ganado/%s/parto', $ganado->id);

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson($this->urlServicioMonta, $this->parto + ['personal_id' => $this->veterinario->id]);

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
        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf($this->urlServicioMonta . '/%s', $idparto));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'parto',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'observacion' => 'string',
                        'crias' => 'array',
                        'crias.0.id' => 'integer',
                        'crias.0.nombre' => 'string',
                        'crias.0.numero' => 'integer',
                        'crias.0.sexo' => 'string',
                        'crias.0.origen' => 'string',
                        'crias.0.fecha_nacimiento' => 'string',
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

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(sprintf($this->urlServicioMonta . '/%s', $idpartoEditar), $this->parto + ['numero_toro' => $this->numero_toro]);

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


        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->deleteJson(sprintf($this->urlServicioMonta . '/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['partoID' => $idToDelete]);
    }


    /* partos con inseminacion */

    public function test_obtener_partos_inseminacion(): void
    {

        $this->generarpartosInseminacion();

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson($this->urlServicioMonta);

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
                        'crias' => 'array',
                        'crias.0.id' => 'integer',
                        'crias.0.nombre' => 'string',
                        'crias.0.numero' => 'integer',
                        'crias.0.sexo' => 'string',
                        'crias.0.origen' => 'string',
                        'crias.0.fecha_nacimiento' => 'string',
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

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson($this->urlServicioInseminacion, $this->parto + ['personal_id' => $this->veterinario->id]);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'parto',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'observacion' => 'string',
                        'crias' => 'array',
                        'crias.0.id' => 'integer',
                        'crias.0.nombre' => 'string',
                        'crias.0.numero' => 'integer',
                        'crias.0.sexo' => 'string',
                        'crias.0.origen' => 'string',
                        'crias.0.fecha_nacimiento' => 'string',
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
        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf($this->urlServicioInseminacion . '/%s', $idparto));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'parto',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'observacion' => 'string',
                        'crias' => 'array',
                        'crias.0.id' => 'integer',
                        'crias.0.nombre' => 'string',
                        'crias.0.numero' => 'integer',
                        'crias.0.sexo' => 'string',
                        'crias.0.origen' => 'string',
                        'crias.0.fecha_nacimiento' => 'string',
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

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(sprintf($this->urlServicioInseminacion . '/%s', $idpartoEditar), $this->parto + ['numero_toro' => $this->numero_toro]);

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


        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->deleteJson(sprintf($this->urlServicioInseminacion . '/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['partoID' => $idToDelete]);
    }


    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_parto(array $parto, array $errores): void
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

        Ganado::factory()
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->hacienda)
            ->create(['nombre' => 'test', 'numero' => 33]);

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson($this->urlServicioInseminacion, $parto);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_obtener_partos_de_todas_las_vacas(): void
    {
        /* partos con monta fallecidos */
        Ganado::factory()
            ->count(5)
            ->hasPeso(1)
            ->hasServicios(7, ['servicioable_id' => $this->toro->id,'servicioable_type' => $this->toro->getMorphClass(), 'personal_id' => $this->veterinario->id])
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

            /* partos con monta  vendidos */
        Ganado::factory()
            ->count(5)
            ->hasPeso(1)
            ->hasServicios(7, ['servicioable_id' => $this->toro->id,'servicioable_type' => $this->toro->getMorphClass(), 'personal_id' => $this->veterinario->id])
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

            /* partos con monta  sanos */
        Ganado::factory()
        ->hasPeso(1)
        ->hasServicios(7, ['servicioable_id' => $this->toro->id,'servicioable_type' => $this->toro->getMorphClass(), 'personal_id' => $this->veterinario->id])
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
            ->count(5)
            ->create();

            /* partos con inseminacion */
        Ganado::factory()
        ->hasPeso(1)
        ->hasServicios(7, ['servicioable_id' => $this->pajuelaToro->id,'servicioable_type' => $this->pajuelaToro->getMorphClass(), 'personal_id' => $this->veterinario->id])
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
        ->count(5)
        ->create();

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('todosPartos'));

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
