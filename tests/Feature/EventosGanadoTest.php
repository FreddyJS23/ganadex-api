<?php

namespace Tests\Feature;

use App\Events\FallecimientoGanado;
use App\Models\Comprador;
use App\Models\Estado;
use App\Models\Fallecimiento;
use App\Models\Finca;
use App\Models\Ganado;
use App\Models\Leche;
use App\Models\Parto;
use App\Models\Personal;
use App\Models\TiposNotifiacion;
use App\Models\Toro;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;
use Illuminate\Support\Str;

class EventosGanadoTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $toro;
    private $ganado;
    private $estado;
    private $veterinario;
    private $numero_toro;
    private int $cantidad_ganado = 50;
    private $finca;

    private array $servicio = [
        'observacion' => 'bien',
        'fecha' => '2020-10-02',
        'tipo' => 'monta'
    ];

    private $parto = [
        'observacion' => 'bien',
        'nombre' => 'test',
        'fecha' => '2020-10-02',
        'numero' => 33,
        'peso_nacimiento' => 50,
    ];
    private $hembra = ['sexo' => 'H'];
    private $macho = ['sexo' => 'M'];



    private array $revision = [
        'diagnostico' => 'prenada',
        'fecha' => '2020-10-02',
        'tratamiento' => 'medicina',
    ];

    private array $revisionDescarte = [
        'diagnostico' => 'descartar',
        'fecha' => '2020-10-02',
        'tratamiento' => 'ninguno',
    ];

    private array $venta = [
        'precio' => 350,
        'fecha' => '2020-10-02',
    ];

    private array $fallecimiento = [
        'causa' => 'enferma',
        'fecha' => '2020-10-02',
    ];

    private array $pesoLeche = [
        'peso_leche' => 30,
        'fecha' => '2020-10-02',

    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->hasConfiguracion()->create();

            $this->finca
            = Finca::factory()
            ->for($this->user)
            ->create();

        $this->user->assignRole('admin');

        $this->estado = Estado::all();

        $this->veterinario
        = Personal::factory()
            ->for($this->finca)
            ->create(['cargo_id' => 2]);

        $this->ganado
            = Ganado::factory()
            ->hasPeso(1)
            ->hasEvento(['prox_revision'=>null,'prox_parto'=>null,'prox_secado'=>null])
            ->hasAttached($this->estado)
            ->for($this->finca)
            ->create(['sexo' => 'H', 'tipo_id' => 3]);

        $this->toro = Toro::factory()
            ->for($this->finca)
            ->for(Ganado::factory()->for($this->finca)->create(['sexo' => 'M']))->create();

        $this->numero_toro = $this->toro->ganado->numero;
    }

    /**
     * A basic feature test example.
     */
    public function test_cuando_se_realiza_un_servicio_tiene_proxima_revision_y_no_esta_pendiente_de_servicio(): void
    {
        //realizar servicio
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/servicio', $this->ganado->id),
            $this->servicio + ['toro_id' => $this->toro->id,'personal_id'=>$this->veterinario->id]
        );
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/ganado/%s', $this->ganado->id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->whereAllType([
                'ganado.eventos.prox_revision' => 'string',
                'servicio_reciente' => 'array',
                'total_servicios' => 'integer'
            ])
            ->where(
                'ganado.estados',
                fn (Collection $estados) => $estados->doesntContain('estado', 'pendiente_servicio')

            )
            ->etc()
        );
    }

    public function test_cuando_se_realiza_una_revision_y_sale_preñada_y_es_primer_parto(): void
    {

        //realizar servicio
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/servicio', $this->ganado->id),
            $this->servicio + ['toro_id' => $this->toro->id,'personal_id' => $this->veterinario->id]
        );

        //realizar revision
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/revision', $this->ganado->id),
            $this->revision + ['personal_id' => $this->veterinario->id]
        );

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/ganado/%s', $this->ganado->id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->whereAllType(
                [
                    'ganado.eventos.prox_parto' => 'string',
                    'ganado.eventos.prox_secado' => 'null',
                    'revision_reciente' => 'array',
                    'total_revisiones' => 'integer',
                ]
            )->has('ganado.estados', 2)
                ->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'gestacion')
                )
                ->etc()
        );
    }

    public function test_cuando_se_realiza_una_revision_y_sale_preñada_ya_tuvo_parto(): void
    {

        //realizar servicio
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/servicio', $this->ganado->id),
            $this->servicio + ['toro_id' => $this->toro->id,'personal_id' => $this->veterinario->id]
        );
        //realizar parto
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/parto', $this->ganado->id),
            $this->parto + $this->hembra + ['personal_id' => $this->veterinario->id]
        );
        //realizar revision
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/revision', $this->ganado->id),
            $this->revision + ['personal_id' => $this->veterinario->id]
        );

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/ganado/%s', $this->ganado->id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->whereAllType(
                [
                    'ganado.eventos.prox_parto' => 'string',
                    'ganado.eventos.prox_secado' => 'string',
                    'revision_reciente' => 'array',
                    'total_revisiones' => 'integer',
                ]
            )->has('ganado.estados', 3)
                ->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'pendiente_secar')
                )
                ->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'gestacion')
                )
                ->etc()
        );
    }

    public function test_cuando_se_realiza_una_revision_y_se_descarta(): void
    {
        //realizar revision
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/revision', $this->ganado->id),
            $this->revisionDescarte + ['personal_id' => $this->veterinario->id]
        );

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->getJson('api/ganado_descarte');

        $response->assertStatus(200)->AssertJson( fn (AssertableJson $json) => $json
                ->has('ganado_descartes', 1,fn (AssertableJson $json) => $json
                    ->has('estados',1,fn(AssertableJson $json)=>$json
                        ->where('estado','sano')->etc())->etc()));
    }


    public function test_cuando_se_realiza_un_parto_empieza_lactancia_y_cambia_adulto(): void
    {
        //realizar servicio
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/servicio', $this->ganado->id),
            $this->servicio + ['toro_id' => $this->toro->id, 'personal_id' => $this->veterinario->id]
        );

        //realizar parto
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/parto', $this->ganado->id),
            $this->parto + $this->hembra + ['personal_id' => $this->veterinario->id]
        );

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/ganado/%s', $this->ganado->id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->whereAllType(
                [
                    'ganado.eventos.prox_revision' => 'string',
                    'servicio_reciente' => 'array',
                    'total_servicios' => 'integer',
                    'parto_reciente' => 'array',
                    'parto_reciente.cria' => 'array',
                    'total_partos' => 'integer',
                ]
            )->has('ganado.estados', 2)
                ->where('ganado.tipo', 'adulto')
                ->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'sano')

                )->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'lactancia')
                )
                ->etc()
        );
    }

    public function test_cuando_se_realiza_un_parto_y_nace_macho(): void
    {
        //realizar servicio
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/servicio', $this->ganado->id),
            $this->servicio + ['toro_id' => $this->toro->id, 'personal_id' => $this->veterinario->id]
        );

        //realizar parto
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/parto', $this->ganado->id),
            $this->parto + $this->macho + ['personal_id' => $this->veterinario->id]
        );

        $cria_id = Parto::select('ganado_cria_id')->where('ganado_id', $this->ganado->id)->first();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/ganado/%s', $cria_id->ganado_cria_id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json
                ->has('ganado.estados', 3)
                ->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'pendiente_numeracion')

                )->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'pendiente_capar')

                )->etc()
        );
    }

    public function test_cuando_se_realiza_un_parto_la_cria_hembra_tiene_que_estar_pendiente_numeracion(): void
    {
        //realizar servicio
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/servicio', $this->ganado->id),
            $this->servicio + ['toro_id' => $this->toro->id, 'personal_id' => $this->veterinario->id]
        );

        //realizar parto
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/parto', $this->ganado->id),
            $this->parto + $this->hembra + ['personal_id' => $this->veterinario->id]
        );

        $cria_id = Parto::select('ganado_cria_id')->where('ganado_id', $this->ganado->id)->first();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/ganado/%s', $cria_id->ganado_cria_id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json
                ->has('ganado.estados', 2)
                ->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'pendiente_numeracion')

                )->etc()
        );
    }

    public function test_cuando_se_realiza_una_venta(): void
    {
        $comprador = Comprador::factory()->for($this->finca)->create();

        $this->venta = $this->venta + ['ganado_id' => $this->ganado->id, 'comprador_id' => $comprador->id];
        //realizar venta
        $response1=$this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->postJson(route('ventas.store'), $this->venta);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/ganado/%s', $this->ganado->id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json
                ->has('ganado.estados', 1)
                ->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'vendido')

                )->etc()
        );
    }

    public function test_cuando_se_registra_fallecimiento_de_una_cabeza_ganado(): void
    {

        $this->fallecimiento = $this->fallecimiento + ['numero_ganado' => $this->ganado->numero];


        //registrar fallecimiento

        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->postJson(route('fallecimientos.store'), $this->fallecimiento);


        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/ganado/%s', $this->ganado->id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json
            /* ->has('ganado.estados', 1) */
            ->where(
                'ganado.estados',
                fn (Collection $estados) => $estados->contains('estado', 'fallecido')

                )->etc()
            );

}

    public function test_cuando_se_realiza_pesaje_mensual_de_leche_ya_no_esta_pendiente_de_pesaje_de_leche(): void
    {
        //realizar pesaje de leche
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->postJson(route('pesaje_leche.store', ['ganado' => $this->ganado->id]), $this->pesoLeche);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/ganado/%s', $this->ganado->id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json
                ->has('ganado.estados', 10)
                ->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->doesntContain('estado', 'pendiente_pesaje_leche')

                )->etc()
        );
    }

    public function test_verificacion_vaca_pendiente_pesaje_leche_este_mes(): void
    {
        $estado = Estado::firstWhere('estado', 'sano');
        $numeroMes=now()->month;
        $fecha=now();

        if($numeroMes <= 11){
            $fecha=now()->addMonths(2);
        }
        else if($numeroMes==12){
            $fecha=now()->subMonths(2);
        }

        $ganadoPendientePesajeLeche = Ganado::factory()
            ->hasPeso(1)
            ->hasEvento(1)
            ->has(
                Leche::factory()->for($this->finca)->state(
                    function (array $attributes, Ganado $ganado) use ($fecha) {
                        return ['ganado_id' => $ganado->id, 'fecha' =>$fecha->format('Y-m-d')];
                    }
                ),
                'pesajes_leche'
            )
            ->hasAttached($estado)
            ->for($this->finca)
            ->create();

        $ganadoConPesajeLecheHecho = Ganado::factory()
            ->hasPeso(1)
            ->hasEvento(1)
            ->has(
                Leche::factory()->for($this->finca)->state(
                    function (array $attributes, Ganado $ganado) {
                        return ['ganado_id' => $ganado->id, 'fecha' => now()->format('Y-m-d')];
                    }
                ),
                'pesajes_leche'
            )
            ->hasAttached($estado)
            ->for($this->finca)
            ->create();

        //evento iniciar sesion finca
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->getJson(route('crear_sesion_finca',['finca'=>$this->finca->id]));

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->getJson(route('ganado.index'));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->has(
               //ganado pendiente pesaje mensual
                'cabezas_ganado.1',
                fn (AssertableJson $json) => $json->has('estados', 3)
                    ->where(
                        'estados',
                        fn (Collection $estados) => $estados->contains('estado', 'pendiente_pesaje_leche')
                    )->etc()
            )->has(
                //ganado con pesaje mensual de leche realizado
                'cabezas_ganado.2',
                //estado:sano,pendiente_servicio
                fn (AssertableJson $json) => $json->has('estados', 2)
                    ->where(
                        'estados',
                        fn (Collection $estados) => $estados->doesntContain('estado', 'pendiente_pesaje_leche')
                    )->etc()
            )
        );
    }

    public function test_generar_notificaciones_cuando_los_eventos_estan_proximos(): void
    {

        //crear ganado con todos los evento proximo
        Ganado::factory()
            ->count(10)
            ->hasPeso(1)
            ->hasEvento()
            ->for($this->finca)
            ->create();

            //11 por que se suma tambien el que se crea en setUp
            $cantidadGanadoEventoProximo=10;

        //ganado con un evento lejano
        Ganado::factory()
            ->count(10)
            ->hasPeso(1)
            ->hasEvento([
                'prox_revision' => now()->addDays(30)->format('Y-m-d'),
                'prox_parto' => now()->addDays(30)->format('Y-m-d'),
                'prox_secado' => now()->addDays(30)->format('Y-m-d'),
            ])
            ->for($this->finca)
            ->create();


       //evento iniciar sesion finca
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->getJson(route('crear_sesion_finca',['finca'=>$this->finca->id]));

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->getJson(route('notificaciones.index'));

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json)
        => $json
            ->has(
                'notificaciones',
                fn (AssertableJson $json)
                => $json
                    ->has('revision', $cantidadGanadoEventoProximo)
                    ->has('secado', $cantidadGanadoEventoProximo)
                    ->has('parto', $cantidadGanadoEventoProximo)
            ));
    }

    public function test_verificacion_vaca_apta_para_servicio(): void
    {
        $estado = Estado::firstWhere('estado', 'sano');

        $ganadoAptoServicio = Ganado::factory()
            ->hasPeso(1,['peso_actual'=>350])
            ->hasEvento(1)
            ->hasAttached($estado)
            ->for($this->finca)
            ->create();

        $ganadoNoAptoServicio = Ganado::factory()
            ->hasPeso(1,['peso_actual'=>200])
            ->hasEvento(1)
            ->hasAttached($estado)
            ->for($this->finca)
            ->create();

        //evento iniciar sesion finca
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->getJson(route('crear_sesion_finca',['finca'=>$this->finca->id]));

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->user->configuracion->peso_servicio,'dias_evento_notificacion'=>$this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->user->configuracion->dias_diferencia_vacuna])->getJson(route('ganado.index'));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->has(
               //ganado pendiente pesaje mensual
                'cabezas_ganado.1',
                fn (AssertableJson $json) => $json->has('estados', 2)
                    ->where(
                        'estados',
                        fn (Collection $estados) => $estados->contains('estado', 'pendiente_servicio')
                    )->etc()
            )->has(
                //ganado con pesaje mensual de leche realizado
                'cabezas_ganado.2',
                fn (AssertableJson $json) => $json->has('estados', 1)
                    ->where(
                        'estados',
                        fn (Collection $estados) => $estados->doesntContain('estado', 'pendiente_servicio')
                    )->etc()
            )
        );
    }
}
