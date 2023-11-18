<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Ganado;
use App\Models\Servicio;
use App\Models\Toro;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class ServicioTest extends TestCase
{
    use RefreshDatabase;

    private array $servicio = [
        'observacion' => 'bien',
        'tipo' => 'Monta'


    ];

    private int $cantidad_servicio = 10;

    private $user;
    private $ganado;
    private $estado;
    private $toro;
    private $numero_toro;
    private $url;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();

        $this->estado = Estado::all();

        $this->ganado
            = Ganado::factory()
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->user)
            ->create();

        $this->toro = Toro::factory()
            ->for($this->user)
            ->for(Ganado::factory()->for($this->user)->create(['sexo' => 'M']))->create();

        $this->numero_toro = $this->toro->ganado->numero;

        $this->url = sprintf('api/ganado/%s/servicio', $this->ganado->id);
    }

    private function generarServicio(): Collection
    {
        return Servicio::factory()
            ->count($this->cantidad_servicio)
            ->for($this->ganado)
            ->for($this->toro)
            ->create();
    }
    public static function ErrorInputProvider(): array
    {
        return [

            'caso de insertar too inexistente' => [
                [
                    'observacion' => 'bien',
                    'numero_toro' => 0,
                    'tipo' => 'monta',
                ], ['numero_toro']
            ],
            'caso de insertar datos erróneos' => [
                [
                    'observacion' => 'te',
                    'numero_toro' => 'hj',
                    'tipo' => 'nose',
                ], ['observacion', 'numero_toro', 'tipo']
            ],
            'caso de no insertar datos requeridos' => [
                [], ['observacion', 'numero_toro', 'tipo']
            ],
        ];
    }



    /**
     * A basic feature test example.
     */

    public function test_obtener_servicios(): void
    {
        $this->generarServicio();

        $response = $this->actingAs($this->user)->getJson($this->url);
        $response->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->has('servicios', $this->cantidad_servicio));
    }


    public function test_creacion_servicio(): void
    {

        $response = $this->actingAs($this->user)->postJson($this->url, $this->servicio + ['numero_toro' => $this->numero_toro]);

        $response->assertStatus(201)->assertJson(['servicio' => true]);
    }


    public function test_obtener_servicio(): void
    {
        $servicios = $this->generarServicio();

        $idRandom = rand(0, $this->cantidad_servicio - 1);
        $idservicio = $servicios[$idRandom]->id;
        $response = $this->actingAs($this->user)->getJson(sprintf($this->url . '/%s', $idservicio));

        $response->assertStatus(200)->assertJson(['servicio' => true]);
    }
    public function test_actualizar_servicio(): void
    {
        $servicios = $this->generarServicio();
        $idRandom = rand(0, $this->cantidad_servicio - 1);
        $idservicioEditar = $servicios[$idRandom]->id;

        $response = $this->actingAs($this->user)->putJson(sprintf($this->url . '/%s', $idservicioEditar), $this->servicio + ['numero_toro' => $this->numero_toro]);

        $response->assertStatus(200)->assertJson(['servicio' => true]);
    }

    public function test_eliminar_servicio(): void
    {
        $servicios = $this->generarServicio();
        $idRandom = rand(0, $this->cantidad_servicio - 1);
        $idToDelete = $servicios[$idRandom]->id;


        $response = $this->actingAs($this->user)->deleteJson(sprintf($this->url . '/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['servicioID' => $idToDelete]);
    }

    public function test_obtener_servicios_de_todas_las_vacas(): void
    {
        Ganado::factory()
            ->count(10)
            ->hasPeso(1)
            ->hasServicios(7, ['toro_id' => $this->toro->id])
            ->hasParto(3, function(array $attributes,Ganado $ganado){
                $cria=Ganado::factory()->create(['user_id'=>$ganado->user_id]);
                return ['toro_id'=>$ganado->servicioReciente->toro_id,'ganado_cria_id'=>$cria->id];
            })
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->user)
            ->create();

        $response = $this->actingAs($this->user)->getJson(route('todasServicios'));
        
        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has('todos_servicios.1', fn (AssertableJson $json) => $json->whereAllType([
                    'id' => 'integer',
                    'numero' => 'integer',
                    'ultimo_servicio' => 'string',
                    'toro' => 'array',
                    'efectividad' => 'float|integer',
                    'total_servicios' => 'integer'
                ]))
            );
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_servicio($servicio, $errores): void
    {

        $response = $this->actingAs($this->user)->postJson($this->url, $servicio);

        $response->assertStatus(422)->assertInvalid($errores);
    }
}
