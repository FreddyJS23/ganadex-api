<?php

namespace Tests\Feature;

use App\Events\FallecimientoGanado;
use App\Models\CausasFallecimiento;
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
use Spatie\Activitylog\Models\Activity;

class LogsOyentesEventosTest extends TestCase
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
        'fecha' => '2020-10-02',
        'descripcion' => 'test',
    ];

    private array $pesoLeche = [
        'peso_leche' => 30,
        'fecha' => '2020-10-02',

    ];

    protected function setUp(): void
    {
        parent::setUp();

        $causaFallecimiento = CausasFallecimiento::factory()->create();
        $this->fallecimiento=$this->fallecimiento + ['causas_fallecimiento_id'=>$causaFallecimiento->id];

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
            ->hasEvento(['prox_revision' => null, 'prox_parto' => null, 'prox_secado' => null])
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
    public function test_log_se_realiza_un_servicio(): void
    {
        //realizar servicio
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/servicio', $this->ganado->id),
            $this->servicio + ['toro_id' => $this->toro->id, 'personal_id' => $this->veterinario->id]
        );

        $numero = $this->ganado->numero;
        $this->assertDatabaseHas('activity_log', [
            'log_name'  => 'servicio',
            'causer_id'   => $this->user->id,
            'description'  => "Animal $numero tiene una proxima revision despues del servicio",
        ]);
    }

    public function test_log_cuando_se_realiza_una_revision_y_sale_preñada_y_es_primer_parto(): void
    {

        //realizar servicio
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/servicio', $this->ganado->id),
            $this->servicio + ['toro_id' => $this->toro->id, 'personal_id' => $this->veterinario->id]
        );

        //realizar revision
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/revision', $this->ganado->id),
            $this->revision + ['personal_id' => $this->veterinario->id]
        );


        $numero = $this->ganado->numero;

        $this->assertDatabaseHas('activity_log', [
            'log_name'  => 'revision',
            'causer_id'   => $this->user->id,
            'description'  => "Animal $numero ahora esta en gestacion",
        ]);
    }

    public function test_log_cuando_se_realiza_una_revision_y_sale_preñada_ya_tuvo_parto(): void
    {

        //realizar servicio
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/servicio', $this->ganado->id),
            $this->servicio + ['toro_id' => $this->toro->id, 'personal_id' => $this->veterinario->id]
        );
        //realizar parto
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/parto', $this->ganado->id),
            $this->parto + $this->hembra + ['personal_id' => $this->veterinario->id]
        );
        //realizar revision
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/revision', $this->ganado->id),
            $this->revision + ['personal_id' => $this->veterinario->id]
        );

        $numero = $this->ganado->numero;
        //solo se comprueba el sesaco ya que en el test anterior se compreba que esta en gestacion
        $this->assertDatabaseHas('activity_log', [
            'log_name'  => 'revision',
            'causer_id'   => $this->user->id,
            'description'  => "Animal $numero ahora tiene fecha de secado",
        ]);
    }

    public function test_log_cuando_se_realiza_una_revision_y_se_descarta(): void
    {
        //realizar revision
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/revision', $this->ganado->id),
            $this->revisionDescarte + ['personal_id' => $this->veterinario->id]
        );

        $numero = $this->ganado->numero;
        $this->assertDatabaseHas('activity_log', [
            'log_name'  => 'revision',
            'causer_id'   => $this->user->id,
            'description'  => "Animal $numero descartado",
        ]);
    }


    public function test_logs_cuando_se_realiza_un_parto_empieza_lactancia_y_cambia_adulto(): void
    {
        //realizar servicio
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/servicio', $this->ganado->id),
            $this->servicio + ['toro_id' => $this->toro->id, 'personal_id' => $this->veterinario->id]
        );

        //realizar parto
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/parto', $this->ganado->id),
            $this->parto + $this->hembra + ['personal_id' => $this->veterinario->id]
        );

        $numero = $this->ganado->numero;
        $this->assertDatabaseHas('activity_log', [
            'log_name'  => 'parto',
            'causer_id'   => $this->user->id,
            'description'  => "Animal $numero tiene una fecha de revision despues del parto",
        ]);

        $this->assertDatabaseHas('activity_log', [
            'log_name'  => 'parto',
            'causer_id'   => $this->user->id,
            'description'  => "Animal $numero ahora tiene estado de lactancia",
        ]);

        $this->assertDatabaseHas('activity_log', [
            'log_name'  => 'parto',
            'causer_id'   => $this->user->id,
            'description'  => "Animal $numero cambia a vaca despues del parto",
        ]);
    }

    /*    public function test_cuando_se_realiza_un_parto_y_nace_macho(): void
    {
        //realizar servicio
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/servicio', $this->ganado->id),
            $this->servicio + ['toro_id' => $this->toro->id, 'personal_id' => $this->veterinario->id]
        );

        //realizar parto
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/parto', $this->ganado->id),
            $this->parto + $this->macho + ['personal_id' => $this->veterinario->id]
        );

        $cria_id = Parto::select('ganado_cria_id')->where('ganado_id', $this->ganado->id)->first();

    } */

    /*   public function test_cuando_se_realiza_un_parto_la_cria_hembra_tiene_que_estar_pendiente_numeracion(): void
    {
        //realizar servicio
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/servicio', $this->ganado->id),
            $this->servicio + ['toro_id' => $this->toro->id, 'personal_id' => $this->veterinario->id]
        );

        //realizar parto
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(
            sprintf('api/ganado/%s/parto', $this->ganado->id),
            $this->parto + $this->hembra + ['personal_id' => $this->veterinario->id]
        );

        $cria_id = Parto::select('ganado_cria_id')->where('ganado_id', $this->ganado->id)->first();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/ganado/%s', $cria_id->ganado_cria_id));

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json) => $json
                ->has('ganado.estados', 2)
                ->where(
                    'ganado.estados',
                    fn(Collection $estados) => $estados->contains('estado', 'pendiente_numeracion')

                )->etc()
        );
    }

    public function test_cuando_se_realiza_una_venta(): void
    {
        $comprador = Comprador::factory()->for($this->finca)->create();

        $this->venta = $this->venta + ['ganado_id' => $this->ganado->id, 'comprador_id' => $comprador->id];
        //realizar venta
        $response1 = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(route('ventas.store'), $this->venta);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/ganado/%s', $this->ganado->id));

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json) => $json
                ->has('ganado.estados', 1)
                ->where(
                    'ganado.estados',
                    fn(Collection $estados) => $estados->contains('estado', 'vendido')

                )->etc()
        );
    }
 */

    public function test_cuando_se_registra_fallecimiento_de_una_cabeza_ganado(): void
    {

        $this->fallecimiento = $this->fallecimiento + ['ganado_id' => $this->ganado->id];

        //registrar fallecimiento

        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(route('fallecimientos.store'), $this->fallecimiento);

        $numero = $this->ganado->numero;
        $this->assertDatabaseHas('activity_log', [
            'log_name'  => 'fallecimiento',
            'causer_id'   => $this->user->id,
            'description'  => "Estado fallecimiento animal $numero",
        ]);
    }

    public function test_log_cuando_se_realiza_pesaje_mensual_de_leche_ya_no_esta_pendiente_de_pesaje_de_leche(): void
    {
        //realizar pesaje de leche
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(route('pesaje_leche.store', ['ganado' => $this->ganado->id]), $this->pesoLeche);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf('api/ganado/%s', $this->ganado->id));

        $numero = $this->ganado->numero;
        $this->assertDatabaseHas('activity_log', [
            'log_name'  => 'pesaje de leche',
            'causer_id'   => $this->user->id,
            'description'  => "Animal $numero ya no esta pendiente de pesaje de leche",
        ]);
    }


    public function test_logs_eventos_inicio_sesion(): void
    {
        //evento iniciar sesion finca
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('crear_sesion_finca', ['finca' => $this->finca->id]));

        $this->assertDatabaseHas('activity_log', [
            'log_name'  => 'edad ganado',
            'causer_id'   => $this->user->id,
            'description'  => "Verificada edad de todos los animales",
        ]);

        $this->assertDatabaseHas('activity_log', [
            'log_name'  => 'notificaciones',
            'causer_id'   => $this->user->id,
            'description'  => "Se han generado las notificaciones",
        ]);

        $this->assertDatabaseHas('activity_log', [
            'log_name'  => 'pesaje mensual leche',
            'causer_id'   => $this->user->id,
            'description'  => "Verificado si hay vacas sin pesar en este mes",
        ]);
    }

    public function test_obtener_los_logs_de_eventos(): void
    {
        //evento iniciar sesion finca
        $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('crear_sesion_finca', ['finca' => $this->finca->id]));

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id, 'peso_servicio' => $this->user->configuracion->peso_servicio, 'dias_evento_notificacion' => $this->user->configuracion->dias_evento_notificacion, 'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('logsEventos.index'));

        $response->assertStatus(200)->assertJson(fn(AssertableJson $json)
        => $json->has(
            'logs_eventos.0',
            fn(AssertableJson $json) => $json->whereAllType([
                'id' => 'integer',
                'operacion' => 'string',
                'descripcion' => 'string',
                'fecha' => 'string',
            ])
        ));
    }
}
