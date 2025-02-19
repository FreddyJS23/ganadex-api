<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Finca;
use App\Models\Ganado;
use App\Models\Jornada_vacunacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class JornadaVacunacionTest extends TestCase
{
    use RefreshDatabase;

    private array $jornadaVacunacion = [
        'fecha_inicio' => '2020-10-02',
        'fecha_fin' => '2020-10-02',
        'vacuna_id' => 4,
    ];

    private int $cantidad_jornadasVacunacion = 10;
    private $user;
    private $finca;
    private $estadoSano;
    private $estadoFallecido;
    private $estadoVendido;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->hasConfiguracion()->create();

            $this->user->assignRole('admin');

            $this->finca
            = Finca::factory()
            ->for($this->user)
            ->create();

            $this->estadoSano = Estado::find(1);
            $this->estadoFallecido = Estado::find(2);
            $this->estadoVendido = Estado::find(5);

            Ganado::factory()
            ->count(30)
            ->for($this->finca)
            ->sequence(
                ['tipo_id' => 1],
                ['tipo_id' => 2],
                ['tipo_id' => 3],
                ['tipo_id' => 4],
            )
            ->hasAttached($this->estadoSano)
            ->create();
    }

    private function generarJornadaVacunacion(): Collection
    {
        return Jornada_vacunacion::factory()
            ->count($this->cantidad_jornadasVacunacion)
            ->for($this->finca)
            ->create();
    }
    public static function ErrorInputProvider(): array
    {
        return [

            'caso de insertar datos erróneos' => [
                [
                    'fecha_inicio' => '20201002',
                    'fecha_fin' => '20201002',
                    'vacuna_id' => 9393,
                ],
                ['fecha_inicio', 'fecha_fin', 'vacuna_id']
            ],
            'caso de no insertar datos requeridos' => [
                [],
                ['fecha_inicio', 'fecha_fin', 'vacuna_id']
            ],
        ];
    }


    public function test_obtener_jornadas_vacunacion(): void
    {
        $this->generarJornadaVacunacion();



        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('jornada_vacunacion.index'));

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json) =>
            $json->whereType('jornadas_vacunacion', 'array')
                ->has('jornadas_vacunacion', $this->cantidad_jornadasVacunacion)
                ->has(
                    'jornadas_vacunacion.0',
                    fn(AssertableJson $json)
                    => $json->whereAllType([
                        'id' => 'integer',
                        'fecha_inicio' => 'string',
                        'fecha_fin' => 'string',
                        'vacuna' => 'string',
                        'vacunados' => 'integer',
                        'ganado_vacunado' => 'array',
                    ])
                )
        );
    }


    public function test_creacion_jornada_vacunacion(): void
    {

        /* ganado con estado fallecido */
        Ganado::factory()
        ->count(30)
        ->for($this->finca)
        ->hasAttached($this->estadoFallecido)
        ->create(  ['tipo_id' => 4]);

        /* ganado con estado vendido */
        Ganado::factory()
        ->count(30)
        ->for($this->finca)
        ->hasAttached($this->estadoVendido)
        ->create(  ['tipo_id' => 4]);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(route('jornada_vacunacion.store'), $this->jornadaVacunacion);

        $response->assertStatus(201)->assertJson(
            fn(AssertableJson $json) => $json->whereAllType([
                'jornada_vacunacion.id' => 'integer',
                'jornada_vacunacion.fecha_inicio' => 'string',
                'jornada_vacunacion.fecha_fin' => 'string',
                'jornada_vacunacion.vacuna' => 'string',
                'jornada_vacunacion.vacunados' => 'integer',
                'jornada_vacunacion.ganado_vacunado' => 'array',
            ])
            ->where('jornada_vacunacion.vacunados',fn(int $vacunados)=> $vacunados <= 30)

        );
    }

    public function test_obtener_jornada_vacunacion(): void
    {
        $jornadasVacunacion = $this->generarJornadaVacunacion();
        $idRandom = random_int(0, $this->cantidad_jornadasVacunacion - 1);
        $idJornadaVacunacion = $jornadasVacunacion[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('jornada_vacunacion.show', $idJornadaVacunacion));

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json) => $json->whereAllType([
                'jornada_vacunacion.id' => 'integer',
                'jornada_vacunacion.fecha_inicio' => 'string',
                'jornada_vacunacion.fecha_fin' => 'string',
                'jornada_vacunacion.vacuna' => 'string',
                'jornada_vacunacion.vacunados' => 'integer',
                'jornada_vacunacion.ganado_vacunado' => 'array',
            ])
        );
    }

    public function test_actualizar_jornada_vacunacion(): void
    {
        $jornadaVacunacion = $this->generarJornadaVacunacion();
        $idRandom = random_int(0, $this->cantidad_jornadasVacunacion - 1);
        $idjornadaVacunacionEditar = $jornadaVacunacion[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(route('jornada_vacunacion.update', $idjornadaVacunacionEditar), $this->jornadaVacunacion);

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json) =>
            $json
                ->where('jornada_vacunacion.fecha_inicio', $this->jornadaVacunacion['fecha_inicio'])
                ->where('jornada_vacunacion.fecha_fin', $this->jornadaVacunacion['fecha_fin'])
                ->whereAllType([
                    'jornada_vacunacion.id' => 'integer',
                    'jornada_vacunacion.fecha_inicio' => 'string',
                    'jornada_vacunacion.fecha_fin' => 'string',
                    'jornada_vacunacion.vacuna' => 'string',
                    'jornada_vacunacion.vacunados' => 'integer',
                    'jornada_vacunacion.ganado_vacunado' => 'array',
                ])
                ->etc()
        );
    }


    public function test_eliminar_jornada_vacunacion(): void
    {
        $jornadasVacunacion = $this->generarJornadaVacunacion();
        $idRandom = random_int(0, $this->cantidad_jornadasVacunacion - 1);
        $idToDelete = $jornadasVacunacion[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->deleteJson(route('jornada_vacunacion.destroy', ['jornada_vacunacion'  => $idToDelete]));

        $response->assertStatus(200)->assertJson(['jornada_vacunacionID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_jornadas_vacunacion($jornadaVacunacion, $errores): void
    {
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(route('jornada_vacunacion.store'), $jornadaVacunacion);

        $response->assertStatus(422)->assertInvalid($errores);
    }
}
