<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Finca;
use App\Models\Ganado;
use App\Models\PajuelaToro;
use App\Models\Personal;
use App\Models\Servicio;
use App\Models\Toro;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Support\Str;
use Tests\TestCase;

class ServicioTest extends TestCase
{
    use RefreshDatabase;

    private array $servicioMonta = [
        'observacion' => 'bien',
        'tipo' => 'monta'
    ];
    private array $servicioInseminacion = [
        'observacion' => 'bien',
        'tipo' => 'inseminacion'
    ];

    private int $cantidad_servicio = 10;

    private $user;
    private $ganado;
    private $veterinario;
    private $estado;
    private $toro;
    private $pajuelaToro ;
    private $url;
    private $finca;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();


            $this->finca
            = Finca::factory()
            ->for($this->user)
            ->create();

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
            ->create();

        $this->toro = Toro::factory()
            ->for($this->finca)
            ->for(Ganado::factory()->for($this->finca)->create(['sexo' => 'M']))->create();

        $this->pajuelaToro = PajuelaToro::factory()->for($this->finca)->create();

        $this->url = sprintf('api/ganado/%s/servicio', $this->ganado->id);
    }

    private function generarServicioMonta(): Collection
    {
       return Servicio::factory()
            ->count($this->cantidad_servicio)
            ->for($this->ganado)
            ->for($this->toro,'servicioable')
            ->create(['personal_id' => $this->veterinario]);
    }

    private function generarServicioInseminacion(): Collection
    {
       return Servicio::factory()
            ->count($this->cantidad_servicio)
            ->for($this->ganado)
            ->for($this->pajuelaToro,'servicioable')
            ->create(['personal_id' => $this->veterinario]);
    }


    public static function ErrorInputProviderMonta(): array
    {
        return [

            'caso de insertar toro inexistente' => [
                [
                    'observacion' => 'bien',
                    'toro_id' => 0,
                    'tipo' => 'monta',
                    'personal_id' => 0
                ], ['toro_id','personal_id']
            ],
            'caso de insertar datos erróneos' => [
                [
                    'observacion' => 'te',
                    'toro_id' => 'hj',
                    'tipo' => 'nose',
                ], ['observacion', 'toro_id', 'tipo','personal_id']
            ],
            'caso de no insertar datos requeridos' => [
                [], ['observacion', 'tipo']
            ],
            'caso de insertar un personal que no sea veterinario' => [
                [
                    'personal_id' => 2
                ], ['personal_id']
            ],
        ];
    }
    public static function ErrorInputProviderInseminacion(): array
    {
        return [

            'caso de insertar pajuela toro inexistente' => [
                [
                    'observacion' => 'bien',
                    'pajuela_toro_id' => 0,
                    'tipo' => 'monta',
                ], ['pajuela_toro_id']
            ],
            'caso de insertar datos erróneos' => [
                [
                    'observacion' => 'te',
                    'pajuela_toro_id' => 'hj',
                    'tipo' => 'nose',
                ], ['observacion', 'pajuela_toro_id', 'tipo']
            ],
            'caso de no insertar datos requeridos' => [
                [], ['observacion', 'tipo']
            ],
            'caso de insertar un personal que no sea veterinario' => [
                [
                    'personal_id' => 2
                ], ['personal_id']
            ],
        ];
    }

    /**
     * A basic feature test example.
     */

    public function test_obtener_servicios_monta(): void
    {
        $this->generarServicioMonta();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->getJson($this->url);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'servicios',
                    $this->cantidad_servicio,
                    fn (AssertableJson $json) => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'observacion' => 'string',
                            'fecha' => 'string',
                        ])->where('tipo',fn (string $tipoServicio)=> Str::contains($tipoServicio, ['Monta', 'Inseminacion']))
                    ->has(
                        'toro',
                        fn (AssertableJson $json)
                        => $json->whereAllType(['id' => 'integer', 'numero' => 'integer'])
                    )
                    ->has(
                        'veterinario',
                        fn (AssertableJson $json)
                        => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                    )
                )
            );
    }


    public function test_creacion_servicio_monta(): void
    {

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->postJson($this->url, $this->servicioMonta + ['toro_id' => $this->toro->id,'personal_id'=>$this->veterinario->id]);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'servicio',
                    fn (AssertableJson $json) => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'observacion' => 'string',
                            'fecha' => 'string',
                        ])->where('tipo', fn (string $tipoServicio) => Str::contains($tipoServicio, ['Monta', 'Inseminacion']))
                    ->has(
                        'toro',
                        fn (AssertableJson $json)
                        => $json->whereAllType(['id' => 'integer', 'numero' => 'integer'])
                    )->has(
                        'veterinario',
                        fn (AssertableJson $json)
                        => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                    )
                )
            );
    }


    public function test_obtener_servicio_monta(): void
    {
        $servicios = $this->generarServicioMonta();

        $idRandom = rand(0, $this->cantidad_servicio - 1);
        $idservicio = $servicios[$idRandom]->id;
        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->getJson(sprintf($this->url . '/%s', $idservicio));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'servicio',
                    fn (AssertableJson $json) => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'observacion' => 'string',
                            'fecha' => 'string',
                        ])->where('tipo', fn (string $tipoServicio) => Str::contains($tipoServicio, ['Monta', 'Inseminacion']))
                    ->has(
                        'toro',
                        fn (AssertableJson $json)
                        => $json->whereAllType(['id' => 'integer', 'numero' => 'integer'])
                    )->has(
                        'veterinario',
                        fn (AssertableJson $json)
                        => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                    )
                )
            );
    }

    public function test_actualizar_servicio_monta(): void
    {
        $servicios = $this->generarServicioMonta();
        $idRandom = rand(0, $this->cantidad_servicio - 1);
        $idservicioEditar = $servicios[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->putJson(sprintf($this->url . '/%s', $idservicioEditar), $this->servicioMonta + ['toro_id' => $this->toro->id]);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'servicio',
                    fn (AssertableJson $json) =>
                    $json->where('observacion', $this->servicioMonta['observacion'])
                    ->where('tipo', ucwords($this->servicioMonta['tipo']))
                    ->has(
                        'veterinario',
                        fn (AssertableJson $json)
                        => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                    )
                    ->etc()
                )
            );
    }

    public function test_eliminar_servicio_monta(): void
    {
    $servicios = $this->generarServicioMonta();
        $idRandom = rand(0, $this->cantidad_servicio - 1);
        $idToDelete = $servicios[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->deleteJson(sprintf($this->url . '/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['servicioID' => $idToDelete]);
    }



    /**
     * @dataProvider ErrorinputProviderMonta
     */
    public function test_error_validacion_registro_servicio_monta($servicio, $errores): void
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
            ]);;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->postJson($this->url, $servicio);

        $response->assertStatus(422)->assertInvalid($errores);
    }



    /*servicio con inseminacion*/

    public function test_obtener_servicios_inseminacion(): void
    {
        $this->generarServicioInseminacion();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->getJson($this->url);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'servicios',
                    $this->cantidad_servicio ,
                    fn (AssertableJson $json) => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'observacion' => 'string',
                            'fecha' => 'string',
                        ])->where('tipo',fn (string $tipoServicio)=> Str::contains($tipoServicio, ['Monta', 'Inseminacion']))
                    ->has(
                        'pajuela_toro',
                        fn (AssertableJson $json)
                        => $json->whereAllType(['id' => 'integer', 'codigo' => 'string'])
                    )
                    ->has(
                        'veterinario',
                        fn (AssertableJson $json)
                        => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                    )
                )
            );
    }


    public function test_creacion_servicio_inseminacion(): void
    {

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->postJson($this->url, $this->servicioInseminacion + ['pajuela_toro_id' => $this->pajuelaToro->id,'personal_id'=>$this->veterinario->id]);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'servicio',
                    fn (AssertableJson $json) => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'observacion' => 'string',
                            'fecha' => 'string',
                        ])->where('tipo', fn (string $tipoServicio) => Str::contains($tipoServicio, ['Monta', 'Inseminacion']))
                    ->has(
                        'pajuela_toro',
                        fn (AssertableJson $json)
                        => $json->whereAllType(['id' => 'integer', 'codigo' => 'string'])
                    )->has(
                        'veterinario',
                        fn (AssertableJson $json)
                        => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                    )
                )
            );
    }


    public function test_obtener_servicio_inseminacion(): void
    {
        $servicios = $this->generarServicioInseminacion();

        $idRandom = rand(0, $this->cantidad_servicio - 1);
        $idservicio = $servicios[$idRandom]->id;
        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->getJson(sprintf($this->url . '/%s', $idservicio));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'servicio',
                    fn (AssertableJson $json) => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'observacion' => 'string',
                            'fecha' => 'string',
                        ])->where('tipo', fn (string $tipoServicio) => Str::contains($tipoServicio, ['Monta', 'Inseminacion']))
                    ->has(
                        'pajuela_toro',
                        fn (AssertableJson $json)
                        => $json->whereAllType(['id' => 'integer', 'codigo' => 'string'])
                    )->has(
                        'veterinario',
                        fn (AssertableJson $json)
                        => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                    )
                )
            );
    }
    public function test_actualizar_servicio_inseminacion(): void
    {
        $servicios = $this->generarServicioInseminacion();
        $idRandom = rand(0, $this->cantidad_servicio - 1);
        $idservicioEditar = $servicios[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->putJson(sprintf($this->url . '/%s', $idservicioEditar), $this->servicioInseminacion + ['pajuela_toro_id' => $this->pajuelaToro->id]);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'servicio',
                    fn (AssertableJson $json) =>
                    $json->where('observacion', $this->servicioInseminacion['observacion'])
                    ->where('tipo',ucwords($this->servicioInseminacion['tipo']))
                    ->has(
                        'veterinario',
                        fn (AssertableJson $json)
                        => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
                    )
                    ->etc()
                )
            );
    }

    public function test_eliminar_servicio_inseminacion(): void
    {
        $servicios = $this->generarServicioInseminacion();
        $idRandom = rand(0, $this->cantidad_servicio - 1);
        $idToDelete = $servicios[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->deleteJson(sprintf($this->url . '/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['servicioID' => $idToDelete]);
    }



    /**
     * @dataProvider ErrorinputProviderInseminacion
     */
    public function test_error_validacion_registro_servicio_inseminacion($servicio, $errores): void
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
            ]);;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->postJson($this->url, $servicio);

        $response->assertStatus(422)->assertInvalid($errores);
    }



    public function test_obtener_servicios_de_todas_las_vacas(): void
    {
        /* partos con monta */
        Ganado::factory()
            ->count(5)
            ->hasPeso(1)
            ->hasServicios(7, ['servicioable_id' => $this->toro->id, 'servicioable_type' => $this->toro->getMorphClass(), 'personal_id' => $this->veterinario->id])
            ->hasParto(3, function (array $attributes, Ganado $ganado) {
                $finca=$ganado->finca->id;
                $veterinario = Personal::factory()->create(['finca_id' => $finca, 'cargo_id' => 2]);
                $cria = Ganado::factory()->create(['finca_id' => $finca]);

                return ['partoable_id' => $ganado->servicioReciente->servicioable->id, 'partoable_type' => $ganado->servicioReciente->servicioable->getMorphClass(), 'ganado_cria_id' => $cria->id, 'personal_id' => $veterinario->id];
            })
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->finca)
            ->create();

        /* partos con inseminacion */
        Ganado::factory()
            ->count(5)
            ->hasPeso(1)
            ->hasServicios(7, ['servicioable_id' => $this->pajuelaToro->id, 'servicioable_type' => $this->pajuelaToro->getMorphClass(), 'personal_id' => $this->veterinario->id])
            ->hasParto(3, function (array $attributes, Ganado $ganado) {
                $finca=$ganado->finca->id;
                $veterinario = Personal::factory()->create(['finca_id' => $finca, 'cargo_id' => 2]);
                $cria = Ganado::factory()->create(['finca_id' => $finca]);


                return ['partoable_id' => $ganado->servicioReciente->servicioable->id, 'partoable_type' => $ganado->servicioReciente->servicioable->getMorphClass(), 'ganado_cria_id' => $cria->id, 'personal_id' => $veterinario->id];
            })
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->finca)
            ->create();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => [$this->finca->id]])->getJson(route('todasServicios'));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has('todos_servicios.1', fn (AssertableJson $json) => $json->whereAllType([
                    'id' => 'integer',
                    'numero' => 'integer',
                    'ultimo_servicio' => 'string',
                    'toro' => 'array',
                    'efectividad' => 'double|integer|null',
                    'total_servicios' => 'integer'
                ]))->has('todos_servicios.6', fn (AssertableJson $json) => $json->whereAllType([
                'id' => 'integer',
                'numero' => 'integer',
                'ultimo_servicio' => 'string',
                'pajuela_toro' => 'array',
                'efectividad' => 'double|integer|null',
                'total_servicios' => 'integer'
            ]))
            );
    }
}
