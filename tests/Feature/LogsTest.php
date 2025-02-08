<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Fallecimiento;
use App\Models\Finca;
use App\Models\Ganado;
use App\Models\Jornada_vacunacion;
use App\Models\Parto;
use App\Models\Personal;
use App\Models\Revision;
use App\Models\Servicio;
use App\Models\Toro;
use App\Models\User;
use App\Models\UsuarioVeterinario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class LogsTest extends TestCase
{
    use RefreshDatabase;


    private array $revision = [
        'diagnostico' => 'revisar',
        'tratamiento' => 'medicina',
        'fecha' => '2020-10-02',

    ];

    private array $servicio = [
        'observacion' => 'bien',
        'tipo' => 'monta',
        'fecha' => '2020-10-02',

    ];

    private array $parto = [
        'observacion' => 'bien',
        'nombre' => 'test',
        'numero' => 33,
        'fecha' => '2020-10-02',

        'sexo' => 'H',
        'peso_nacimiento' => 33,
    ];

    private array $jornadaVacunacion = [
        'fecha_inicio' => '2020-10-02',
        'fecha_fin' => '2020-10-02',
        'vacuna_id' => 4,
    ];

    private array $fallecimiento = [
        'causa' => 'enferma',
        'fecha' => '2020-10-02',
    ];

    private $userAdmin;
    private $userVeterinario;
    private $finca;
    private $estado;
    private $veterinario;
    private $servicioHecho;
    private $ganado;
    private $toro;
    private $infoUserVeterinario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userAdmin
            = User::factory()
            ->hasConfiguracion()
            ->create(['usuario' => 'admin', 'password' => Hash::make('admin')]);

            $this->userAdmin->assignRole('admin');

            $this->finca
            = Finca::factory()
            ->for($this->userAdmin)
            ->create();

        $this->userVeterinario
            = User::factory()
            ->create(['usuario' => 'veterinario', 'password' => Hash::make('veterinario')]);

          $this->infoUserVeterinario = UsuarioVeterinario::factory()
            ->for(Personal::factory()->for($this->finca)->create(['cargo_id' => 2]), 'veterinario')
            ->create(['admin_id' => $this->userAdmin->id,
            'user_id'=>$this->userVeterinario->id]);

            $this->userVeterinario->assignRole('veterinario');

        $this->estado = Estado::all();

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
            ->create(['sexo' => 'H', 'tipo_id' => 3]);

        $this->toro = Toro::factory()
            ->for($this->finca)
            ->for(Ganado::factory()->for($this->finca)->create(['sexo' => 'M']))->create();

            $this->servicioHecho = Servicio::factory()
            ->for($this->ganado)
            ->for($this->toro,'servicioable')
            ->create(['personal_id' => $this->veterinario]);

    }

    public function test_verificacion_log_login_usuario_veterinario(): void
    {
        $this->withHeader('origin', config('app.url'))->postJson('api/login', [
            'usuario' => 'veterinario',
            'password' => 'veterinario',
        ]);

        $this->assertDatabaseHas('activity_log', [
            'causer_type' => User::class,
            'causer_id'   => $this->userVeterinario->id,
            'log_name'  => 'Login',
        ]);
    }

    public function test_verificacion_log_login_usuario_admin(): void
    {
        $this->withHeader('origin', config('app.url'))->postJson('api/login', [
            'usuario' => 'admin',
            'password' => 'admin',
        ]);

        $this->assertDatabaseHas('activity_log', [
            'causer_type' => User::class,
            'causer_id'   => $this->userAdmin->id,
            'log_name'  => 'Login',
        ]);
    }

    public function test_verificacion_log_veterinario_hace_revision(): void
    {
        $response= $this->actingAs($this->userVeterinario)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->postJson(route('revision.store',['ganado'=>$this->ganado->id]), $this->revision + ['personal_id'=>$this->veterinario->id]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Revision::class,
            'causer_id'   => $this->userVeterinario->id,
            'description'  => 'created',
        ]);
    }

    public function test_verificacion_no_generar_log_admin_hace_revision(): void
    {
        $response= $this->actingAs($this->userAdmin)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->postJson(route('revision.store',['ganado'=>$this->ganado->id]), $this->revision + ['personal_id'=>$this->veterinario->id]);

        $this->assertDatabaseMissing('activity_log', [
            'subject_type' => Revision::class,
            'causer_id'   => $this->userAdmin->id,
            'description'  => 'created',
        ]);
    }

    public function test_verificacion_log_veterinario_hace_servicio(): void
    {
        $response= $this->actingAs($this->userVeterinario)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->postJson(route('servicio.store',['ganado'=>$this->ganado->id]), $this->servicio + ['toro_id'=>$this->toro->id, 'personal_id'=>$this->veterinario->id]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Servicio::class,
            'causer_id'   => $this->userVeterinario->id,
            'description'  => 'created',
        ]);
    }

    public function test_verificacion_no_generar_log_admin_hace_servicio(): void
    {
        $response= $this->actingAs($this->userAdmin)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->postJson(route('servicio.store',['ganado'=>$this->ganado->id]), $this->servicio + ['toro_id'=>$this->toro->id, 'personal_id'=>$this->veterinario->id]);

        $this->assertDatabaseMissing('activity_log', [
            'subject_type' => Servicio::class,
            'causer_id'   => $this->userAdmin->id,
            'description'  => 'created',
        ]);
    }

     public function test_verificacion_log_veterinario_atiende_parto(): void
    {
        $response= $this->actingAs($this->userVeterinario)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->postJson(route('parto.store',['ganado'=>$this->ganado->id]), $this->parto + [ 'personal_id'=>$this->veterinario->id]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Parto::class,
            'causer_id'   => $this->userVeterinario->id,
            'description'  => 'created',
        ]);
    }

    public function test_verificacion_no_generar_log_admin_atiende_parto(): void
    {
        $response= $this->actingAs($this->userAdmin)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->postJson(route('parto.store',['ganado'=>$this->ganado->id]), $this->parto + [ 'personal_id'=>$this->veterinario->id]);

        $this->assertDatabaseMissing('activity_log', [
            'subject_type' => Parto::class,
            'causer_id'   => $this->userAdmin->id,
            'description'  => 'created',
        ]);
    }


    public function test_verificacion_log_veterinario_atiende_jornada_vacunacion(): void
    {
        $response= $this->actingAs($this->userVeterinario)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->postJson(route('jornada_vacunacion.store'), $this->jornadaVacunacion + [ 'personal_id'=>$this->veterinario->id]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Jornada_vacunacion::class,
            'causer_id'   => $this->userVeterinario->id,
            'description'  => 'created',
        ]);
    }

    public function test_verificacion_no_generar_log_admin_atiende_jornada_vacunacion(): void
    {
        $response= $this->actingAs($this->userAdmin)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->postJson(route('jornada_vacunacion.store'), $this->jornadaVacunacion + [ 'personal_id'=>$this->veterinario->id]);

        $this->assertDatabaseMissing('activity_log', [
            'subject_type' => Jornada_vacunacion::class,
            'causer_id'   => $this->userAdmin->id,
            'description'  => 'created',
        ]);
    }


    public function test_verificacion_no_generar_log_admin_realiza_fallecimiento(): void
    {
        $response= $this->actingAs($this->userAdmin)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->postJson(route('fallecimientos.store'), $this->fallecimiento + [ 'ganado_id'=>$this->ganado->id]);

        $this->assertDatabaseMissing('activity_log', [
            'subject_type' => Fallecimiento::class,
            'causer_id'   => $this->userAdmin->id,
            'description'  => 'created',
        ]);
    }


    public function test_verificacion_log_veterinario_registra_fallecimiento(): void
    {
        $response= $this->actingAs($this->userVeterinario)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->postJson(route('fallecimientos.store'), $this->fallecimiento + [ 'ganado_id'=>$this->ganado->id]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Fallecimiento::class,
            'causer_id'   => $this->userVeterinario->id,
            'description'  => 'created',
        ]);
    }



    public function test_admin_obetiene_logs_veterinario(): void
    {
        //login veterinario
        $this->withHeader('origin', config('app.url'))->postJson('api/login', [
            'usuario' => 'veterinario',
            'password' => 'veterinario',
        ]);

        //veteterinario hace revision
        $this->actingAs($this->userVeterinario)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->postJson(route('revision.store',['ganado'=>$this->ganado->id]), $this->revision + ['personal_id'=>$this->veterinario->id]);

        //veteterinario hace servicio
        $this->actingAs($this->userVeterinario)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->postJson(route('servicio.store',['ganado'=>$this->ganado->id]), $this->servicio + ['toro_id'=>$this->toro->id, 'personal_id'=>$this->veterinario->id]);

        //veteterinario hace parto
        $this->actingAs($this->userVeterinario)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->postJson(route('parto.store',['ganado'=>$this->ganado->id]), $this->parto + [ 'personal_id'=>$this->veterinario->id]);

        //veteterinario hace jornada vacunacion
        $response= $this->actingAs($this->userVeterinario)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->postJson(route('jornada_vacunacion.store'), $this->jornadaVacunacion + [ 'personal_id'=>$this->veterinario->id]);

        //veteterinario hace fallecimiento
        $response= $this->actingAs($this->userVeterinario)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->postJson(route('fallecimientos.store'), $this->fallecimiento + [ 'ganado_id'=>$this->ganado->id]);


        $response= $this->actingAs($this->userAdmin)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->getJson(route('logsVeterinario.index',['usuario_veterinario'=>$this->infoUserVeterinario->id]));

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json)
         => $json->has('logs',6,
         fn (AssertableJson $json) => $json->whereAllType([
            'id' => 'integer',
            'actividad' => 'string',
            'actividad_id' => 'integer|null',
            'fecha' => 'string',
        ])));
    }


    public function test_error_usuario_veterinario_obtiene_logs(): void
    {

       $otroAdmin = User::factory()
        ->hasConfiguracion()
        ->create(['usuario' => 'adminOtro', 'password' => Hash::make('admin')]);


        //login veterinario
        $this->withHeader('origin', config('app.url'))->postJson('api/login', [
            'usuario' => 'veterinario',
            'password' => 'veterinario',
        ]);

        //veteterinario hace revision
        $this->actingAs($this->userVeterinario)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->postJson(route('revision.store',['ganado'=>$this->ganado->id]), $this->revision + ['personal_id'=>$this->veterinario->id]);

        //veteterinario hace servicio
        $this->actingAs($this->userVeterinario)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->postJson(route('servicio.store',['ganado'=>$this->ganado->id]), $this->servicio + ['toro_id'=>$this->toro->id, 'personal_id'=>$this->veterinario->id]);

        //veteterinario hace parto
        $this->actingAs($this->userVeterinario)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->postJson(route('parto.store',['ganado'=>$this->ganado->id]), $this->parto + [ 'personal_id'=>$this->veterinario->id]);

        //veteterinario hace jornada vacunacion
        $this->actingAs($this->userVeterinario)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->postJson(route('jornada_vacunacion.store'), $this->jornadaVacunacion + [ 'personal_id'=>$this->veterinario->id]);

        $response= $this->actingAs($this->userVeterinario)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->getJson(route('logsVeterinario.index',['usuario_veterinario'=>$this->infoUserVeterinario->id]));

        $response->assertStatus(403);
    }

    public function test_error_otro_admin_obetiene_logs_veterinario_que_no_le_pertenece(): void
    {

       $otroAdmin = User::factory()
        ->hasConfiguracion()
        ->create(['usuario' => 'adminOtro', 'password' => Hash::make('admin')]);


        //login veterinario
        $this->withHeader('origin', config('app.url'))->postJson('api/login', [
            'usuario' => 'veterinario',
            'password' => 'veterinario',
        ]);

        //veteterinario hace revision
        $this->actingAs($this->userVeterinario)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->postJson(route('revision.store',['ganado'=>$this->ganado->id]), $this->revision + ['personal_id'=>$this->veterinario->id]);

        //veteterinario hace servicio
        $this->actingAs($this->userVeterinario)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->postJson(route('servicio.store',['ganado'=>$this->ganado->id]), $this->servicio + ['toro_id'=>$this->toro->id, 'personal_id'=>$this->veterinario->id]);

        //veteterinario hace parto
        $this->actingAs($this->userVeterinario)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->postJson(route('parto.store',['ganado'=>$this->ganado->id]), $this->parto + [ 'personal_id'=>$this->veterinario->id]);

        //veteterinario hace jornada vacunacion
        $this->actingAs($this->userVeterinario)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->postJson(route('jornada_vacunacion.store'), $this->jornadaVacunacion + [ 'personal_id'=>$this->veterinario->id]);

        $response= $this->actingAs($otroAdmin)->withSession(['finca_id' => $this->finca->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->getJson(route('logsVeterinario.index',['usuario_veterinario'=>$this->infoUserVeterinario->id]));

        $response->assertStatus(403);
    }

}
