<?php

namespace Tests\Feature;

use App\Models\CausasFallecimiento;
use App\Models\Fallecimiento;
use App\Models\Plan_sanitario;
use App\Models\Parto;
use App\Models\Revision;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;
use Tests\Feature\Common\NeedsEstado;
use Tests\Feature\Common\NeedsGanado;
use Tests\Feature\Common\NeedsPersonal;
use Tests\Feature\Common\NeedsSetupRequest;
use Tests\Feature\Common\NeedsToro;

class LogsTest extends TestCase
{
    use NeedsSetupRequest,
    NeedsPersonal,
    NeedsGanado,
    NeedsEstado,
    NeedsToro {
    NeedsSetupRequest::setUp as needsSetupRequestSetUp;
    NeedsEstado::setUp as needsEstadoSetUp;
    NeedsToro::setUp as needsToroSetUp;
    NeedsPersonal::setUp as needsPersonalSetUp;
}


    private array $revision = [
        'tratamiento' => 'medicina',
        'fecha' => '2020-10-02',
        'observacion' => 'bien',
        'dosis' => 30,
    ];

    private array $servicio = [
        'observacion' => 'bien',
        'tipo' => 'monta',
        'fecha' => '2020-10-02',

    ];

    private array $parto = [
        'observacion' => 'bien',
        'fecha' => '2020-10-02',
        'crias'=>[
            [ 'nombre' => 'testcria',
            'numero' => 33,
            'sexo' => 'H',
            'peso_nacimiento' => 50,]
        ]
    ];

    private array $jornadaVacunacion = [
        'fecha_inicio' => '2020-10-02',
        'fecha_fin' => '2020-10-02',
        'vacuna_id' => 4,
    ];

    private array $fallecimiento = [
        'descripcion' => 'enferma',
        'fecha' => '2020-10-02',
    ];

    private $userAdmin;
    private $servicioHecho;

    protected function setUp(): void
    {

        $this->needsSetupRequestSetUp();
        $this->needsEstadoSetUp();
        $this->needsToroSetUp();
        $this->needsPersonalSetUp();
        $this->generarGanado();

        $this->userAdmin=$this->user;

        //tipo de revision preñada
        $this->revision=$this->revision + ['tipo_revision_id' => 1];

        $causaFallecimiento = CausasFallecimiento::factory()->create();
        $this->fallecimiento=$this->fallecimiento + ['causas_fallecimiento_id'=>$causaFallecimiento->id];


            $this->servicioHecho = Servicio::factory()
            ->for($this->ganado)
            ->for($this->toro, 'servicioable')
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
        $response = $this->setUpRequest(true)
        ->postJson(route('revision.store', ['ganado' => $this->ganado->id]), $this->revision + ['personal_id' => $this->veterinario->id]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Revision::class,
            'causer_id'   => $this->userVeterinario->id,
            'description'  => 'created',
        ]);
    }

   /*  public function test_verificacion_no_generar_log_admin_hace_revision(): void
    {
        $response= $this->actingAs($this->userAdmin)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->postJson(route('revision.store',['ganado'=>$this->ganado->id]), $this->revision + ['personal_id'=>$this->veterinario->id]);

        $this->assertDatabaseMissing('activity_log', [
            'subject_type' => Revision::class,
            'causer_id'   => $this->userAdmin->id,
            'description'  => 'created',
        ]);
    }
 */
    public function test_verificacion_log_veterinario_hace_servicio(): void
    {
        $response = $this->setUpRequest(true)
        ->postJson(route('servicio.store', ['ganado' => $this->ganado->id]), $this->servicio + ['toro_id' => $this->toro->id, 'personal_id' => $this->veterinario->id]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Servicio::class,
            'causer_id'   => $this->userVeterinario->id,
            'description'  => 'created',
        ]);
    }

    /* public function test_verificacion_no_generar_log_admin_hace_servicio(): void
    {
        $response= $this->actingAs($this->userAdmin)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->postJson(route('servicio.store',['ganado'=>$this->ganado->id]), $this->servicio + ['toro_id'=>$this->toro->id, 'personal_id'=>$this->veterinario->id]);

        $this->assertDatabaseMissing('activity_log', [
            'subject_type' => Servicio::class,
            'causer_id'   => $this->userAdmin->id,
            'description'  => 'created',
        ]);
    } */

    public function test_verificacion_log_veterinario_atiende_parto(): void
    {
        //añadir estado gestacion
        $this->ganado->estados()->attach([3]);

        $response = $this->setUpRequest(true)
        ->postJson(route('parto.store', ['ganado' => $this->ganado->id]), $this->parto + [ 'personal_id' => $this->veterinario->id]);

        $this->assertDatabaseHas('activity_log', [
           'subject_type' => Parto::class,
           'causer_id'   => $this->userVeterinario->id,
           'description'  => 'created',
        ]);
    }

   /*  public function test_verificacion_no_generar_log_admin_atiende_parto(): void
    {
        $response= $this->actingAs($this->userAdmin)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->postJson(route('parto.store',['ganado'=>$this->ganado->id]), $this->parto + [ 'personal_id'=>$this->veterinario->id]);

        $this->assertDatabaseMissing('activity_log', [
            'subject_type' => Parto::class,
            'causer_id'   => $this->userAdmin->id,
            'description'  => 'created',
        ]);
    } */


    public function test_verificacion_log_veterinario_atiende_plan_sanitario(): void
    {
        $response = $this->setUpRequest(true)
        ->postJson(route('plan_sanitario.store'), $this->jornadaVacunacion + [ 'personal_id' => $this->veterinario->id]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Plan_sanitario::class,
            'causer_id'   => $this->userVeterinario->id,
            'description'  => 'created',
        ]);
    }

 /*    public function test_verificacion_no_generar_log_admin_atiende_plan_sanitario(): void
    {
        $response= $this->actingAs($this->userAdmin)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->postJson(route('plan_sanitario.store'), $this->jornadaVacunacion + [ 'personal_id'=>$this->veterinario->id]);

        $this->assertDatabaseMissing('activity_log', [
            'subject_type' => Plan_sanitario::class,
            'causer_id'   => $this->userAdmin->id,
            'description'  => 'created',
        ]);
    } */


   /*  public function test_verificacion_no_generar_log_admin_realiza_fallecimiento(): void
    {
        $response= $this->actingAs($this->userAdmin)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio'=>$this->userAdmin->configuracion->peso_servicio,'dias_Evento_notificacion'=>$this->userAdmin->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna'=>$this->userAdmin->configuracion->dias_diferencia_vacuna])->postJson(route('fallecimientos.store'), $this->fallecimiento + [ 'ganado_id'=>$this->ganado->id]);

        $this->assertDatabaseMissing('activity_log', [
            'subject_type' => Fallecimiento::class,
            'causer_id'   => $this->userAdmin->id,
            'description'  => 'created',
        ]);
    } */


    public function test_verificacion_log_veterinario_registra_fallecimiento(): void
    {
        $response = $this->setUpRequest(true)
        ->postJson(route('fallecimientos.store'), $this->fallecimiento + [ 'ganado_id' => $this->ganado->id]);

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

        //veteterinario hace servicio
        $this->setUpRequest(true)
        ->postJson(route('servicio.store', ['ganado' => $this->ganado->id]), $this->servicio + ['toro_id' => $this->toro->id, 'personal_id' => $this->veterinario->id]);

        //veteterinario hace revision
        $this->setUpRequest(true)
        ->postJson(route('revision.store', ['ganado' => $this->ganado->id]), $this->revision + ['personal_id' => $this->veterinario->id]);

        //veteterinario hace parto
        $this->setUpRequest(true)
        ->postJson(route('parto.store', ['ganado' => $this->ganado->id]), $this->parto + [ 'personal_id' => $this->veterinario->id]);

        //veteterinario hace jornada vacunacion
         $this->setUpRequest(true)
         ->postJson(route('plan_sanitario.store'), $this->jornadaVacunacion + [ 'personal_id' => $this->veterinario->id]);

        //veteterinario hace fallecimiento
         $this->setUpRequest(true)
         ->postJson(route('fallecimientos.store'), $this->fallecimiento + [ 'ganado_id' => $this->ganado->id]);

             /* el veterinario hasta aqui deberia tener 16 registros:
            // login sesion hacienda
                login
                edad_ganado
                pesaje mensual leche
                generacion de notificaciones
                verificacion de vacas aptas para servicio
            //operaciones
                servicio
                    creacion
                    // animal tiene proxima revision
                revision
                    creacion
                    animal esta en gestacion
                parto
                    creacion
                    fecha proxima revision despues parto
                    ahora tiiene estado lactancia
                    animal cambia a vaca despues del parto
                plan sanitatio
                    creacion
                fallecimiento
                    creacion
                    estado fallecido al animal
             */


        $response = $this->setUpRequest()->getJson(route('logsVeterinario.index', ['usuario_veterinario' => $this->infoUsuarioVeterinario->id]));

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
         => $json->has(
             'logs',
             16,
             fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType([
                'id' => 'integer',
                'actividad' => 'string',
                'actividad_id' => 'integer|null',
                'fecha' => 'string',
             ])
         ));
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
        $this->setUpRequest(true)->postJson(route('revision.store', ['ganado' => $this->ganado->id]), $this->revision + ['personal_id' => $this->veterinario->id]);

        //veteterinario hace servicio
        $this->setUpRequest(true)->postJson(route('servicio.store', ['ganado' => $this->ganado->id]), $this->servicio + ['toro_id' => $this->toro->id, 'personal_id' => $this->veterinario->id]);

        //veteterinario hace parto
        $this->setUpRequest(true)->postJson(route('parto.store', ['ganado' => $this->ganado->id]), $this->parto + [ 'personal_id' => $this->veterinario->id]);

        //veteterinario hace jornada vacunacion
        $this->setUpRequest(true)->postJson(route('plan_sanitario.store'), $this->jornadaVacunacion + [ 'personal_id' => $this->veterinario->id]);

        $response = $this->setUpRequest(true)->getJson(route('logsVeterinario.index', ['usuario_veterinario' => $this->infoUsuarioVeterinario->id]));

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
        $this->setUpRequest(true)->postJson(route('revision.store', ['ganado' => $this->ganado->id]), $this->revision + ['personal_id' => $this->veterinario->id]);

        //veteterinario hace servicio
        $this->setUpRequest(true)->postJson(route('servicio.store', ['ganado' => $this->ganado->id]), $this->servicio + ['toro_id' => $this->toro->id, 'personal_id' => $this->veterinario->id]);

        //veteterinario hace parto
        $this->setUpRequest(true)->postJson(route('parto.store', ['ganado' => $this->ganado->id]), $this->parto + [ 'personal_id' => $this->veterinario->id]);

        //veteterinario hace jornada vacunacion
        $this->setUpRequest(true)->postJson(route('plan_sanitario.store'), $this->jornadaVacunacion + [ 'personal_id' => $this->veterinario->id]);

        $response = $this->actingAs($otroAdmin)
        ->withSession(['hacienda_id' => $this->hacienda->id,
        'peso_servicio' => $this->userAdmin->configuracion->peso_servicio,
        'dias_Evento_notificacion' => $this->userAdmin->configuracion->dias_evento_notificacion,
        'dias_diferencia_vacuna' => $this->userAdmin->configuracion->dias_diferencia_vacuna])
        ->getJson(route('logsVeterinario.index', ['usuario_veterinario' => $this->infoUsuarioVeterinario->id]));

        $response->assertStatus(403);
    }
}
