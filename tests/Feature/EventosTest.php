<?php

namespace Tests\Feature;

use App\Events\FallecimientoGanado;
use App\Models\CausasFallecimiento;
use App\Models\Comprador;
use App\Models\Estado;
use App\Models\Fallecimiento;
use App\Models\Hacienda;
use App\Models\Ganado;
use App\Models\Leche;
use App\Models\Parto;
use App\Models\PartoCria;
use App\Models\Personal;
use App\Models\RespuestasSeguridad;
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
use Spatie\Activitylog\Models\Activity;


enum Sexo: string {
    case macho = 'M';
    case hembra = 'H';
    case toro = 'T';
};

class EventosTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $toro;
    private $ganado;
    private $estadoSano;
    private $estadoPendientePesajeLeche;
    private $veterinario;
    private $numero_toro;
    private int $cantidad_ganado = 50;
    private $hacienda;
    private $revisionGestacion;
    private $revisionAborto;

    private array $servicio = [
        'observacion' => 'bien',
        'fecha' => '2020-10-02',
        'tipo' => 'monta'
    ];

    private array $partoInputs = [
        'observacion' => 'bien',
        'fecha' => '2020-10-02',
        'crias'=>[
            [ 'nombre' => 'test',
            'numero' => 33,
            'peso_nacimiento' => 50,]
        ]
    ];


    private array $revision = [
        'fecha' => '2020-10-02',
        'tratamiento' => 'medicina',
    ];

    private array $revisionDescarte = [
        'fecha' => '2020-10-02',
        'tratamiento' => 'ninguno',
    ];

    private array $venta = [
        'precio' => 350,
        'fecha' => '2020-10-02',
    ];

    private array $fallecimiento = [
        'fecha' => '2020-10-02',
    ];

    private array $pesoLeche = [
        'peso_leche' => 30,
        'fecha' => '2020-10-02',

    ];

    private array $respuestasSeguridad = [
        'pregunta_seguridad_id' => 1,
        'respuesta' => 'muy bien',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        //tipo de revision preñada
        $this->revisionGestacion=$this->revision + ['tipo_revision_id' => 1];

        //tipo de revision descartear
        $this->revisionDescarte=$this->revisionDescarte + ['tipo_revision_id' => 2];

        //tipo de revision aborto
        $this->revisionAborto=$this->revision + ['tipo_revision_id' => 3];

        $this->user
            = User::factory()->hasConfiguracion()->create();

            $this->hacienda
            = Hacienda::factory()
            ->for($this->user)
            ->create();

        $this->user->assignRole('admin');

        $this->estadoSano = Estado::find(1);

        $this->estadoPendientePesajeLeche = Estado::find(11);


        $this->veterinario
        = Personal::factory()
            ->for($this->user)->hasAttached($this->hacienda)
            ->create(['cargo_id' => 2]);

        $this->ganado
            = Ganado::factory()
            ->hasPeso(1)
            ->hasEvento(['prox_revision' => null,'prox_parto' => null,'prox_secado' => null])
            ->hasAttached($this->estadoSano)
            ->for($this->hacienda)
            ->create(['sexo' => 'H', 'tipo_id' => 3]);

        $this->toro = Toro::factory()
            ->for($this->hacienda)
            ->for(Ganado::factory()->for($this->hacienda)->create(['sexo' => 'M']))->create();

        $this->numero_toro = $this->toro->ganado->numero;

    }

    public function parto(Sexo $sexo)
    {
        $parto= array_merge($this->partoInputs['crias'][0],['sexo'=>$sexo->value]);

        return array_merge($this->partoInputs,['crias'=>[$parto]]);
    }

    /* -----------------------------Evento Servicio hecho ----------------------------- */
    public function test_cuando_se_realiza_un_servicio_tiene_proxima_revision_y_no_esta_pendiente_de_servicio(): void
    {
        //realizar servicio
        $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/servicio', $this->ganado->id),
            $this->servicio + ['toro_id' => $this->toro->id,'personal_id' => $this->veterinario->id]
        );
        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/ganado/%s', $this->ganado->id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType([
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

    /* ------------------------- Evento Revision preñada ------------------------ */

    public function test_cuando_se_realiza_una_revision_y_sale_preñada_y_es_primer_parto(): void
    {

        //realizar servicio
        $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/servicio', $this->ganado->id),
            $this->servicio + ['toro_id' => $this->toro->id,'personal_id' => $this->veterinario->id]
        );

        //realizar revision
        $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/revision', $this->ganado->id),
            $this->revisionGestacion + ['personal_id' => $this->veterinario->id]
        );

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/ganado/%s', $this->ganado->id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType(
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

    /* como ya tiene un becerro, al diagnosticar una revision gestacion,
    se debe activar el campo de secado para retirar la cria dias antes del parto */
    public function test_cuando_se_realiza_una_revision_y_sale_preñada_ya_tuvo_parto(): void
    {

        //realizar servicio
        $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/servicio', $this->ganado->id),
            $this->servicio + ['toro_id' => $this->toro->id,'personal_id' => $this->veterinario->id]
        );

         //realizar revision gestacion
         $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/revision', $this->ganado->id),
            $this->revisionGestacion + ['personal_id' => $this->veterinario->id]
        );

        //realizar parto
        $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/parto', $this->ganado->id),
            $this->parto(Sexo::hembra) + ['personal_id' => $this->veterinario->id]
        );

        //realizar revision gestacion
        $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/revision', $this->ganado->id),
            $this->revisionGestacion + ['personal_id' => $this->veterinario->id]
        );

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/ganado/%s', $this->ganado->id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType(
                [
                    'ganado.eventos.prox_parto' => 'string',
                    'ganado.eventos.prox_secado' => 'string',
                    'revision_reciente' => 'array',
                    'total_revisiones' => 'integer',
                ]
            )->has('ganado.estados', 5)
                ->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'sano')
                )
                ->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'gestacion')
                )
                ->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'pendiente_pesaje_leche')
                )
                ->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'pendiente_secar')
                )

                ->etc()
        );
    }

    /* ------------------------ Evento revision descarte ------------------------ */
    public function test_cuando_se_realiza_una_revision_y_se_descarta(): void
    {
        $estados=Estado::all();
        //añadir estados a un ganado que sera descartado
        $this->ganado->estados()->sync($estados);

        //realizar revision
        $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/revision', $this->ganado->id),
            $this->revisionDescarte + ['personal_id' => $this->veterinario->id]
        );

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson('api/ganado_descarte');

        $response->assertStatus(200)->AssertJson(fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                ->has('ganado_descartes', 1, fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                    ->has('estados', 1, fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson=>$json
                        ->where('estado', 'sano')->etc())->etc()));
    }


    /* ------------------------ Evento revision aborto ------------------------ */
    public function test_cuando_se_realiza_una_revision_aborto(): void
    {
        //realizar servicio
        $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/servicio', $this->ganado->id),
            $this->servicio + ['toro_id' => $this->toro->id,'personal_id' => $this->veterinario->id]
        );

        //realizar revision gestacion
        $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/revision', $this->ganado->id),
            $this->revisionGestacion + ['personal_id' => $this->veterinario->id]
        );

        //realizar revision aborto
        $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/revision', $this->ganado->id),
            $this->revisionAborto + ['personal_id' => $this->veterinario->id]
        );

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/ganado/%s', $this->ganado->id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType(
                [
                    'ganado.eventos.prox_parto' => 'null',
                    'ganado.eventos.prox_secado' => 'null',
                ]
            )->has('ganado.estados', 1)
                ->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'sano')
                )
                ->etc()
        );
    }


    public function test_cuando_se_realiza_una_revision_aborto_y_tiene_una_cria(): void
    {
        //realizar servicio
        $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/servicio', $this->ganado->id),
            $this->servicio + ['toro_id' => $this->toro->id,'personal_id' => $this->veterinario->id]
        );

        //realizar revision gestacion
        $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/revision', $this->ganado->id),
            $this->revisionGestacion + ['personal_id' => $this->veterinario->id]
        );

         //realizar parto
         $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/parto', $this->ganado->id),
            $this->parto(Sexo::hembra) + ['personal_id' => $this->veterinario->id]
        );


        //realizar servicio
        $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/servicio', $this->ganado->id),
            $this->servicio + ['toro_id' => $this->toro->id,'personal_id' => $this->veterinario->id]
        );

        //realizar revision gestacion
        $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/revision', $this->ganado->id),
            $this->revisionGestacion + ['personal_id' => $this->veterinario->id]
        );

        //realizar revision aborto
        $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/revision', $this->ganado->id),
            $this->revisionAborto + ['personal_id' => $this->veterinario->id]
        );

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/ganado/%s', $this->ganado->id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType(
                [
                    'ganado.eventos.prox_parto' => 'null',
                    'ganado.eventos.prox_secado' => 'null',
                ]
            )->has('ganado.estados', 3)
                ->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'sano')
                )
                ->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'lactancia')
                )
                ->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'pendiente_pesaje_leche')
                )
                ->etc()
        );
    }



    /* --------------------------- Evento parto hecho --------------------------- */
    public function test_cuando_se_realiza_un_parto_empieza_lactancia_y_cambia_adulto_y_ya_no_debe_tener_evento_proximo_parto_y_debe_estar_pendiente_pesaje_leche(): void
    {
        //realizar servicio
        $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/servicio', $this->ganado->id),
            $this->servicio + ['toro_id' => $this->toro->id, 'personal_id' => $this->veterinario->id]
        );

         //realizar revision gestacion
         $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/revision', $this->ganado->id),
            $this->revisionGestacion + ['personal_id' => $this->veterinario->id]
        );

        //realizar parto
        $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/parto', $this->ganado->id),
            $this->parto(Sexo::hembra) + ['personal_id' => $this->veterinario->id]
        );

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/ganado/%s', $this->ganado->id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType(
                [
                    'ganado.eventos.prox_revision' => 'string',
                    'ganado.eventos.prox_parto' => 'null',
                    'servicio_reciente' => 'array',
                    'total_servicios' => 'integer',
                    'parto_reciente' => 'array',
                    'parto_reciente.crias' => 'array',
                    'total_partos' => 'integer',
                ]
            )->has('ganado.estados', 3)
                ->where('ganado.tipo', 'Adulto')
                ->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'sano')
                )->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'lactancia')
                )
                ->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'pendiente_pesaje_leche')
                )
                ->etc()
        );
    }

    public function test_cuando_se_realiza_un_parto_y_nace_macho(): void
    {
        //realizar servicio
        $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/servicio', $this->ganado->id),
            $this->servicio + ['toro_id' => $this->toro->id, 'personal_id' => $this->veterinario->id]
        );

         //realizar revision gestacion
         $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/revision', $this->ganado->id),
            $this->revisionGestacion + ['personal_id' => $this->veterinario->id]
        );

        //realizar parto
        $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/parto', $this->ganado->id),
            $this->parto(Sexo::macho) + ['personal_id' => $this->veterinario->id]
        );

        $cria_id = Parto::select('id')->where('ganado_id', $this->ganado->id)->first()->ganado_cria->ganado_id;

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/ganado/%s', $cria_id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                ->has('ganado.estados', 1)
                ->etc()
        );
    }

    public function test_cuando_se_realiza_un_parto_y_sera_criado_para_toro(): void
    {
        //realizar servicio
        $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/servicio', $this->ganado->id),
            $this->servicio + ['toro_id' => $this->toro->id, 'personal_id' => $this->veterinario->id]
        );

         //realizar revision gestacion
         $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/revision', $this->ganado->id),
            $this->revisionGestacion + ['personal_id' => $this->veterinario->id]
        );

        //realizar parto
        $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/parto', $this->ganado->id),
            $this->parto(Sexo::toro) + ['personal_id' => $this->veterinario->id]
        );

        $cria_id = Parto::select('id')->where('ganado_id', $this->ganado->id)->first()->ganado_cria->ganado_id;

        $toro_id = Toro::select('id')->where('ganado_id', $cria_id)->first();

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('toro.show',['toro' => $toro_id->id]));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                ->has('toro',fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                $json->where('tipo','Becerro')
                ->where('sexo','M')
                ->etc()
                )
                ->etc()
        );
    }

/* --------------------------- Evento venta ganado -------------------------- */
    public function test_cuando_se_realiza_una_venta(): void
    {
        $comprador = Comprador::factory()->for($this->hacienda)->create();

        $this->venta = $this->venta + ['ganado_id' => $this->ganado->id, 'comprador_id' => $comprador->id];
        //realizar venta
        $response1 = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(route('ventas.store'), $this->venta);

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/ganado/%s', $this->ganado->id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                ->has('ganado.estados', 1)
                ->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'vendido')
                )->etc()
        );
    }
    public function test_cuando_se_realiza_una_venta_por_lotes(): void
    {
       $ganados= $this->ganado
        = Ganado::factory()
        ->count(3)
        ->hasPeso(1)
        ->hasEvento(['prox_revision' => null,'prox_parto' => null,'prox_secado' => null])
        ->hasAttached($this->estadoSano)
        ->for($this->hacienda)
        ->create(['sexo' => 'H', 'tipo_id' => 3]);

        $comprador = Comprador::factory()->for($this->hacienda)->create();

        $data = [
            'fecha' => '2025-04-20',
            'ganado_ids' => $ganados->pluck('id')->toArray(),
            'comprador_id' => $comprador->id,
        ];
        //realizar venta
         $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(route('ventas.storeBatch'), $data);

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('ganado.index'));

        $response->assertStatus(200)
        ->assertJson(
            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json->has(
                    'cabezas_ganado.1',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->where(
                            'estados',
                            fn (Collection $estados) => $estados->contains('estado', 'vendido')
                        )->etc()

                )
        );
    }

    /* ----------------------- Evento fallecimiento ganado ---------------------- */
    public function test_cuando_se_registra_fallecimiento_de_una_cabeza_ganado(): void
    {

        $this->fallecimiento = $this->fallecimiento + ['ganado_id' => $this->ganado->id];
        $this->fallecimiento['causas_fallecimiento_id'] = CausasFallecimiento::factory()->create()->id;


        //registrar fallecimiento

        $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(route('fallecimientos.store'), $this->fallecimiento);


        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/ganado/%s', $this->ganado->id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
            /* ->has('ganado.estados', 1) */
            ->where(
                'ganado.estados',
                fn (Collection $estados) => $estados->contains('estado', 'fallecido')
            )->etc()
        );
    }

    /* ---------------------- Evento pesaje de leche hecho ---------------------- */
    public function test_cuando_se_realiza_pesaje_mensual_de_leche_ya_no_esta_pendiente_de_pesaje_de_leche(): void
    {
        //añadir estado pendiente de pesaje de leche
        $this->ganado->estados()->attach($this->estadoPendientePesajeLeche);

        //realizar pesaje de leche
        $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(route('pesaje_leche.store', ['ganado' => $this->ganado->id]), $this->pesoLeche);

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/ganado/%s', $this->ganado->id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                ->has('ganado.estados', 1)
                ->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->doesntContain('estado', 'pendiente_pesaje_leche')
                )->etc()
        );
    }

    /* ----------------------- Evento inicio sesion hacienda ----------------------- */
    public function test_verificacion_vaca_pendiente_pesaje_leche_este_mes(): void
    {
        //estados para que pueda estar pendiente pesaje de leche o estados cuando el pesaje de leche ya se realizo
        $estados = Estado::whereIn('estado', ['sano','lactancia',])->get();

        //estados cuando ya esta pendiente pesaje de leche
        $estadosPendientePesajeLeche = Estado::wherein('estado', ['sano','pendiente_pesaje_leche','lactancia'])
        ->get();

        $estadoSano=$estados->firstWhere('estado','sano');

        $fecha = now();
        $numeroMes = $fecha->month;

        /* operacion para el mes de la fecha actual para asi poder crear un ganado
            con pesaje de leche de meses anterior al mes actual
         para que asi pueda estar pendiente del pesaje de leche del mes actual */
        if ($numeroMes <= 11) {
            $fecha = now()->addMonths(2);
        } elseif ($numeroMes == 12) {
            $fecha = now()->subMonths(2);
        }


        $posiblesEcenarios=[
            /*  ganado que deberia estar pendiente pesaje de leche del mes actual */
            ['estados'=>$estados,'uso_fecha_actual'=>false],
            /* ganado que ya se le realizo el pesaje de leche del mes actual */
            ['estados'=>$estados,'uso_fecha_actual'=>true],
            /* ganado que ya se verifico y tienen el estado pendiente pesaje de leche */
            ['estados'=>$estadosPendientePesajeLeche,'uso_fecha_actual'=>false],
            /* //ganado que ya ha tenido pesajes de leche pero actualmente no esta en lactancia */
            ['estados'=>$estadoSano,'uso_fecha_actual'=>false],
        ];

        foreach ($posiblesEcenarios as $posibleEcenario) {
            Ganado::factory()
            ->hasPeso(1)
            ->hasEvento(1)
            ->has(
                Leche::factory()->for($this->hacienda)->state(
                    fn(array $attributes, Ganado $ganado): array => ['ganado_id' => $ganado->id,
                        'fecha' =>$posibleEcenario['uso_fecha_actual'] ? now()->format('Y-m-d') : $fecha->format('Y-m-d')]
                ),
                'pesajes_leche'
            )
            ->hasAttached($posibleEcenario['estados'])
            ->for($this->hacienda)
            ->create();
        }


        //evento iniciar sesion hacienda
        $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('crear_sesion_hacienda', ['hacienda' => $this->hacienda->id]));

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('ganado.index'));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json->has(
               //ganado pendiente pesaje mensual
                'cabezas_ganado.1',
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has('estados', 4)
                    ->where(
                        'estados',
                        fn (Collection $estados) => $estados->contains('estado', 'pendiente_pesaje_leche')
                    )->etc()
            )->has(
                //ganado con pesaje mensual de leche realizado
                'cabezas_ganado.2',
                //estado:sano,lactancia,pendiente_servicio
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has('estados', 3)
                    ->where(
                        'estados',
                        fn (Collection $estados) => $estados->doesntContain('estado', 'pendiente_pesaje_leche')
                    )->etc()
            )
            ->has(
                //ganado con estado pendiente pesaje de leche
                'cabezas_ganado.3',
                //estado:sano,lactancia,pendiente_servicio
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has('estados', 4)
                    ->where(
                        'estados',
                        fn (Collection $estados) => $estados->contains('estado', 'pendiente_pesaje_leche')
                    )->etc()
            )
            ->has(
                //ganado con estado sasno
                'cabezas_ganado.4',
                //estado:sano,pendiente_servicio
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has('estados', 2)
                   ->etc()
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
            ->for($this->hacienda)
            ->create();

            //11 por que se suma tambien el que se crea en setUp
            $cantidadGanadoEventoProximo = 10;

        //ganado con un evento lejano
        Ganado::factory()
            ->count(10)
            ->hasPeso(1)
            ->hasEvento([
                'prox_revision' => now()->addDays(30)->format('Y-m-d'),
                'prox_parto' => now()->addDays(30)->format('Y-m-d'),
                'prox_secado' => now()->addDays(30)->format('Y-m-d'),
            ])
            ->for($this->hacienda)
            ->create();

/* Se hace el evento de inicio de sesion varias veces para confirmar de que no se generare notificaciones repetidas */
       //evento iniciar sesion hacienda
        $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('crear_sesion_hacienda', ['hacienda' => $this->hacienda->id]));
        //evento iniciar sesion hacienda
        $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('crear_sesion_hacienda', ['hacienda' => $this->hacienda->id]));
        //evento iniciar sesion hacienda
        $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('crear_sesion_hacienda', ['hacienda' => $this->hacienda->id]));


        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('notificaciones.index'));

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
        => $json
            ->has(
                'notificaciones',
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
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
            ->hasPeso(1, ['peso_actual' => 350])
            ->hasEvento(1)
            ->hasAttached($estado)
            ->for($this->hacienda)
            ->create();

        $ganadoNoAptoServicio = Ganado::factory()
            ->hasPeso(1, ['peso_actual' => 200])
            ->hasEvento(1)
            ->hasAttached($estado)
            ->for($this->hacienda)
            ->create();

        //evento iniciar sesion hacienda
        $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('crear_sesion_hacienda', ['hacienda' => $this->hacienda->id]));

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('ganado.index'));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json->has(
               //ganado pendiente apta para servicio
               //estado:sano,pendiente_servicio
                'cabezas_ganado.1',
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has('estados', 2)
                    ->where(
                        'estados',
                        fn (Collection $estados) => $estados->contains('estado', 'pendiente_servicio')
                    )->etc()
            )->has(
                //ganado no apto para servicio
                //estado:sano
                'cabezas_ganado.2',
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has('estados', 1)
                    ->where(
                        'estados',
                        fn (Collection $estados) => $estados->doesntContain('estado', 'pendiente_servicio')
                    )->etc()
            )
        );
    }

    public function test_verificacion_edad_ganado(): void
    {
        //becerros que deberian pasar a maute
        $becerros = Ganado::factory()
        ->hasPeso(1)
        ->count(2)
        ->hasEvento(1)
        ->hasAttached($this->estadoSano)
        ->for($this->hacienda)
        //fecha de nacimiento 400 dias atras
        ->create(['tipo_id'=>1,'fecha_nacimiento'=>now()->subDay(400)->format('Y-m-d')]);

        //mautes que deberian pasar a novillo
        $mautes = Ganado::factory()
        ->hasPeso(1)
        ->count(2)
        ->hasEvento(1)
        ->hasAttached($this->estadoSano)
        ->for($this->hacienda)
        //fecha de nacimiento 1000 dias atras
        ->create(['tipo_id'=>2,'fecha_nacimiento'=>now()->subDay(1000)->format('Y-m-d')]);

 //evento iniciar sesion hacienda
 $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('crear_sesion_hacienda', ['hacienda' => $this->hacienda->id]));


        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson('api/ganado');
        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                //se empieza por la posicion 1 ya que  ya hay un ganado registrado
                $json->has(
                        'cabezas_ganado.1',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                            $json->where('tipo','Maute')
                            ->etc()
                    )
                    ->has(
                        'cabezas_ganado.4',
                        fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                            $json->where('tipo','Novillo')
                            ->etc()
                    )
                ->etc()
            );

    }

    /* -------------- evento cuando se crean preguntas de seguridad ------------- */
    /* por ahora no hay evento como tal, la logica se guarda en el mismo controlador de respuestasSeguridad */

    public function test_evento_cuando_se_crean_preguntas_de_seguridad(): void
    {
        $this->user->assignRole('admin');

        //añadir preguntas y respuestas de seguridad al usuario
        /* se añaden dos por factorys y una manual, para que en la manual,
        se active que el usuario tiene el minimo de preguntas de seguridad, el minimo es 3 */
        RespuestasSeguridad::factory()
        ->count(3)
        ->for($this->user)
        ->create();
        $this->actingAs($this->user)->postJson(route('respuestas_seguridad.store'), $this->respuestasSeguridad);

        $response = $this->actingAs($this->user)->getJson(route('usuario.show', ['user' => $this->user->id]));
        $response->assertStatus(200)
        ->assertJson(
            fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                'user',
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                $json->where('tiene_preguntas_seguridad', true)
                ->etc()
            )
        );


    }
}
