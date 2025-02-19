<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Finca;
use App\Models\Ganado;
use App\Models\Personal;
use App\Models\Revision;
use App\Models\Servicio;
use App\Models\Toro;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class RevisionTest extends TestCase
{
    use RefreshDatabase;

    private array $revision = [
        'tratamiento' => 'medicina',
        'fecha' => '2020-10-02',

    ];

    private int $cantidad_revision = 10;

    private $user;
    private $ganado;
    private $estado;
    private $estadoSano;
    private $estadoVendido;
    private $estadoFallecido;
    private $estadoPendienteServicio;
    private $veterinario;
    private $url;
    private $finca;

    protected function setUp(): void
    {
        parent::setUp();

        //tipo de revision preñada
        $this->revision=$this->revision + ['tipo_revision_id' => 3];

        $this->estado = Estado::all();

        $this->estadoSano = Estado::find(1);
        $this->estadoVendido = Estado::find(2);
        $this->estadoFallecido = Estado::find(5);
        $this->estadoPendienteServicio = Estado::find(7);

        $this->user
            = User::factory()->hasConfiguracion()->create();

        $this->user->assignRole('admin');

            $this->finca
            = Finca::factory()
            ->for($this->user)
            ->create();

        $this->veterinario
        = Personal::factory()
            ->for($this->finca)
            ->create(['cargo_id' => 2]);

            $this->ganado
            = Ganado::factory()
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->finca)
            ->create();

        $this->url = sprintf('api/ganado/%s/revision', $this->ganado->id);
    }

    private function generarRevision(): Collection
    {
        return Revision::factory()
            ->count($this->cantidad_revision)
            ->for($this->ganado)
            ->create(['personal_id' => $this->veterinario]);
    }
    public static function ErrorInputProvider(): array
    {
        return [

            'caso de insertar datos erróneos' => [
                [
                    'tipo_revision_id' => 'd',
                    'tratamiento' => 'hj',
                    'personal_id' => 'd'
                ], ['tipo_revision_id', 'tratamiento','personal_id']
            ],
            'caso de no insertar datos requeridos' => [
                [], ['tipo_revision_id', 'tratamiento','personal_id']
            ],
            'caso de insertar un personal que no sea veterinario' => [
                [
                    'tipo_revision_id' => 167,
                    'personal_id' => 2,
                ], ['tipo_revision_id', 'personal_id']
            ],
        ];
    }



    /**
     * A basic feature test example.
     */

    public function test_obtener_revisiones(): void
    {
        $this->generarRevision();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson($this->url);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'revisiones',
                    $this->cantidad_revision,
                    fn (AssertableJson $json) =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'diagnostico' => 'string',
                        'tratamiento' => 'string',
                    ])->has(
                        'veterinario',
                        fn(AssertableJson $json)
                        =>$json->whereAllType([
                            'id' => 'integer',
                        'nombre' => 'string'])
                    )
                )
            );
    }


    public function test_creacion_revision(): void
    {

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson($this->url, $this->revision + ['personal_id' => $this->veterinario->id]);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'revision',
                    fn (AssertableJson $json) =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'diagnostico' => 'string',
                        'tratamiento' => 'string',
                    ])->has(
                        'veterinario',
                        fn (AssertableJson $json)
                        => $json->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string'
                        ])
                    )
                )
            );
    }

    public function test_creacion_revision_y_vaca_no_cumple_requisito_peso_para_diagnosticar_preñada(): void
    {
        $ganadoNoRequisito = Ganado::factory()
        ->hasPeso(['peso_actual' => 200])
        ->hasEvento(1)
        ->hasAttached($this->estado)
        ->for($this->finca)
        ->create();

        $veterinario
        = Personal::factory()
            ->for($this->finca)
            ->create(['cargo_id' => 2]);

            $toro = Toro::factory()
            ->for($this->finca)
            ->for(Ganado::factory()->for($this->finca)->create(['sexo' => 'M']))->create();

            Servicio::factory()
            ->count(1)
            ->for($ganadoNoRequisito)
            ->for($toro, 'servicioable')
            ->create(['personal_id' => $this->veterinario]);



        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])
        ->postJson(route('revision.store', ['ganado' => $ganadoNoRequisito->id]), ['tipo_revision_id' => 1,'tratamiento' => 'medicina', 'fecha' => '2020-10-02','personal_id' => $this->veterinario->id]);

        $response->assertStatus(422)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('errors.tipo_revision_id.0', fn(string $message)=>Str::contains($message, 'La vaca debe tener un peso mayor a'))
                ->etc()
            );
    }

    public function test_creacion_revision_y_vaca_no_cumple_requisito_servicio_para_diagnosticar_preñada(): void
    {
        $ganadoNoRequisito = Ganado::factory()
        ->hasPeso(['peso_actual' => 500])
        ->hasEvento(1)
        ->hasAttached($this->estado)
        ->for($this->finca)
        ->create();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])
        ->postJson(route('revision.store', ['ganado' => $ganadoNoRequisito->id]), ['tipo_revision_id' => 1,'tratamiento' => 'medicina', 'fecha' => '2020-10-02','personal_id' => $this->veterinario->id]);

        $response->assertStatus(422)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('errors.tipo_revision_id.0', 'La vaca debe de tener un servicio previo')
                ->etc()
            )
            ;
    }

    /*Al querer registrar una revision preñada, nose puede decir que esta preñada del mismo servicio
    por ejemplo, si realiza un parto, se puede inmediatamente registrar una revision preñada, esto internamente
    resultaria como otro parto a base del mismo servicio, con eso se asegura de que cada parto que se haga pertenesca
    a un servicio diferente*/
    public function test_creacion_error_revision_preñada_servicio_antiguo(): void
    {
        $ganado = Ganado::factory()
        ->hasPeso(['peso_actual' => 500])
        ->hasEvento(1)
        ->hasAttached($this->estadoPendienteServicio)
        ->for($this->finca)
        ->create();

        $veterinario
        = Personal::factory()
            ->for($this->finca)
            ->create(['cargo_id' => 2]);

            $toro = Toro::factory()
            ->for($this->finca)
            ->for(Ganado::factory()->for($this->finca)->create(['sexo' => 'M']))->create();

            Servicio::factory()
            ->count(1)
            ->for($ganado)
            ->for($toro, 'servicioable')
            ->create(['personal_id' => $this->veterinario]);


        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])
        ->postJson(route('revision.store', ['ganado' => $ganado->id]), ['tipo_revision_id' => 1,'tratamiento' => 'medicina', 'fecha' => '2020-10-02','personal_id' => $this->veterinario->id]);

        $response->assertStatus(422)
            ->assertJson(
                fn (AssertableJson $json) => $json->where('errors.tipo_revision_id.0', 'Realize un nuevo servicio, el servicio anterior ya se utilizo para el parto ya registrado')
                ->etc()
            )
            ;
    }


    public function test_obtener_revision(): void
    {
        $revisiones = $this->generarRevision();

        $idRandom = random_int(0, $this->cantidad_revision - 1);
        $idRevision = $revisiones[$idRandom]->id;
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf($this->url . '/%s', $idRevision));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'revision',
                    fn (AssertableJson $json) =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'diagnostico' => 'string',
                        'tratamiento' => 'string',
                    ])->has(
                        'veterinario',
                        fn (AssertableJson $json)
                        => $json->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string'
                        ])
                    )
                )
            );
    }
    public function test_actualizar_revision(): void
    {
        $revisiones = $this->generarRevision();
        $idRandom = random_int(0, $this->cantidad_revision - 1);
        $idRevisionEditar = $revisiones[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(sprintf($this->url . '/%s', $idRevisionEditar), $this->revision);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'revision',
                    fn (AssertableJson $json) =>
                    $json->where('diagnostico', 'Rutina')
                    ->where('tratamiento', $this->revision['tratamiento'])
                    ->has(
                        'veterinario',
                        fn (AssertableJson $json)
                        => $json->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string'
                        ])
                    )
                    ->etc()
                )
            );
    }

    public function test_eliminar_revision(): void
    {
        $revisiones = $this->generarRevision();
        $idRandom = random_int(0, $this->cantidad_revision - 1);
        $idToDelete = $revisiones[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->deleteJson(sprintf($this->url . '/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['revisionID' => $idToDelete]);
    }

    public function test_obtener_revisiones_de_todas_las_vacas(): void
    {
        //creacion ganado fallecido
        Ganado::factory()
            ->count(3)
            ->hasPeso(1)
            ->hasRevision(5, ['personal_id' => $this->veterinario->id])
            ->hasEvento(1)
            ->hasAttached($this->estadoFallecido)
            ->for($this->finca)
            ->create();

            //creacion ganado vendido
        Ganado::factory()
            ->count(3)
            ->hasPeso(1)
            ->hasRevision(5, ['personal_id' => $this->veterinario->id])
            ->hasEvento(1)
            ->hasAttached($this->estadoVendido)
            ->for($this->finca)
            ->create();

            //creacion ganado sano
        Ganado::factory()
            ->count(5)
            ->hasPeso(1)
            ->hasRevision(5, ['personal_id' => $this->veterinario->id])
            ->hasEvento(1)
            ->hasAttached($this->estadoSano)
            ->for($this->finca)
            ->create();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('todasRevisiones'));
        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has('todas_revisiones', 6 , fn (AssertableJson $json) => $json->whereAllType([
                    'id' => 'integer',
                    'numero' => 'integer',
                    'diagnostico' => 'string',
                    'ultima_revision' => 'string',
                    'proxima_revision' => 'string|null',
                    'total_revisiones' => 'integer'
                ]))
            );
    }


    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_revision($revision, $errores): void
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

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson($this->url, $revision);

        $response->assertStatus(422)->assertInvalid($errores);
    }
}
