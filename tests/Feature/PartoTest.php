<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Finca;
use App\Models\Ganado;
use App\Models\PajuelaToro;
use App\Models\Parto;
use App\Models\Personal;
use App\Models\Servicio;
use App\Models\Toro;
use App\Models\User;
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
        'nombre' => 'test',
        'numero' => 33,
        'fecha' => '2020-10-02',

        'sexo' => 'H',
        'peso_nacimiento' => 33,


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
    private $estado;
    private $numero_toro;
    private $urlServicioMonta;
    private $urlServicioInseminacion;
    private $finca;

    protected function setUp(): void
    {
        parent::setUp();

        $this->estado = Estado::all();

        $this->user
            = User::factory()->create();

            $this->finca
            = Finca::factory()
            ->hasAttached($this->user)
            ->create();

        $this->ganadoServicioMonta
            = Ganado::factory()
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->finca)
            ->create();

            $this->ganadoServicioInseminacion
            = Ganado::factory()
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->finca)
            ->create();

        $this->toro = Toro::factory()
            ->for($this->finca)
            ->for(Ganado::factory()->for($this->finca)->create(['sexo' => 'M']))->create();

            $this->pajuelaToro = PajuelaToro::factory()
        ->for($this->finca)
        ->create();

        $this->veterinario
        = Personal::factory()
        ->for($this->finca)
        ->create(['cargo_id' => 2]);

        $this->servicioMonta = Servicio::factory()
            ->for($this->ganadoServicioMonta)
            ->for($this->toro,'servicioable')
            ->create(['personal_id' => $this->veterinario]);

            $this->servicioInseminacion = Servicio::factory()
            ->for($this->ganadoServicioInseminacion)
            ->for($this->pajuelaToro,'servicioable')
            ->create(['personal_id' => $this->veterinario]);


        $this->urlServicioMonta = sprintf('api/ganado/%s/parto', $this->ganadoServicioMonta->id);

        $this->urlServicioInseminacion = sprintf('api/ganado/%s/parto', $this->ganadoServicioInseminacion->id);
    }

    private function generarpartosMonta(): Collection
    {
        return Parto::factory()
            ->count($this->cantidad_parto)
            ->for($this->ganadoServicioMonta)
            ->for(Ganado::factory()->for($this->finca)->hasAttached($this->estado), 'ganado_cria')
            ->for($this->toro,'partoable')
            ->create(['personal_id' => $this->veterinario]);
    }

    private function generarpartosInseminacion(): Collection
    {
        return Parto::factory()
            ->count($this->cantidad_parto)
            ->for($this->ganadoServicioMonta)
            ->for(Ganado::factory()->for($this->finca)->hasAttached($this->estado), 'ganado_cria')
            ->for($this->pajuelaToro,'partoable')
            ->create(['personal_id' => $this->veterinario]);
    }

    public static function ErrorInputProvider(): array
    {
        return [

            'caso de insertar datos erróneos' => [
                [
                    'observacion' => 'bi',
                    'nombre' => 'te',
                    'numero' => 'd3',
                    'sexo' => 'macho',
                    'peso_nacimiento' => '33mKG',
                ], ['observacion', 'nombre', 'numero', 'sexo', 'peso_nacimiento']
            ],
            'caso de no insertar datos requeridos' => [
                [], ['observacion', 'nombre', 'sexo']
            ],
            'caso de que exista el nombre o numero' => [
                [
                    'observacion',
                    'nombre' => 'test',
                    'numero' => 33,
                    'sexo' => 'H',
                    'tipo_id' => '4',
                    'peso_nacimiento' => 30,
                ], ['nombre', 'numero']
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

    public function test_obtener_partos_monta(): void
    {

        $this->generarpartosMonta();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->getJson($this->urlServicioMonta);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'partos',
                    $this->cantidad_parto,
                    fn (AssertableJson $json) =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'observacion' => 'string',
                        'cria' => 'array',
                        'cria.id' => 'integer',
                        'cria.nombre' => 'string',
                        'cria.numero' => 'integer',
                        'cria.sexo' => 'string',
                        'cria.origen' => 'string',
                        'cria.fecha_nacimiento' => 'string',
                    ])->has(
                    'padre_toro',
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


    public function test_creacion_parto_monta(): void
    {

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->postJson($this->urlServicioMonta, $this->parto + ['personal_id'=>$this->veterinario->id]);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'parto',
                    fn (AssertableJson $json) =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'observacion' => 'string',
                        'cria' => 'array',
                        'cria.id' => 'integer',
                        'cria.nombre' => 'string',
                        'cria.numero' => 'integer',
                        'cria.sexo' => 'string',
                        'cria.origen' => 'string',
                        'cria.fecha_nacimiento' => 'string',
                    ])->has(
                    'padre_toro',
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


    public function test_obtener_parto_monta(): void
    {
        $partos = $this->generarpartosMonta();

        $idRandom = rand(0, $this->cantidad_parto - 1);
        $idparto = $partos[$idRandom]->id;
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->getJson(sprintf($this->urlServicioMonta . '/%s', $idparto));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'parto',
                    fn (AssertableJson $json) =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'observacion' => 'string',
                        'cria' => 'array',
                        'cria.id' => 'integer',
                        'cria.nombre' => 'string',
                        'cria.numero' => 'integer',
                        'cria.sexo' => 'string',
                        'cria.origen' => 'string',
                        'cria.fecha_nacimiento' => 'string',
                    ])->has(
                    'padre_toro',
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
    public function test_actualizar_parto_monta(): void
    {
        $partos = $this->generarpartosMonta();
        $idRandom = rand(0, $this->cantidad_parto - 1);
        $idpartoEditar = $partos[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->putJson(sprintf($this->urlServicioMonta . '/%s', $idpartoEditar), $this->parto + ['numero_toro' => $this->numero_toro]);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->where('parto.observacion', $this->parto['observacion'])
            ->has(
                'parto.veterinario',
                fn (AssertableJson $json)
                => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
            )
                ->etc()
        );
    }


    public function test_eliminar_parto_monta(): void
    {
        $partos = $this->generarpartosInseminacion();
        $idRandom = rand(0, $this->cantidad_parto - 1);
        $idToDelete = $partos[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->deleteJson(sprintf($this->urlServicioMonta . '/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['partoID' => $idToDelete]);
    }


    /* partos con inseminacion */

    public function test_obtener_partos_inseminacion(): void
    {

        $this->generarpartosInseminacion();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->getJson($this->urlServicioMonta);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'partos',
                    $this->cantidad_parto,
                    fn (AssertableJson $json) =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'observacion' => 'string',
                        'cria' => 'array',
                        'cria.id' => 'integer',
                        'cria.nombre' => 'string',
                        'cria.numero' => 'integer',
                        'cria.sexo' => 'string',
                        'cria.origen' => 'string',
                        'cria.fecha_nacimiento' => 'string',
                    ])->has(
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


    public function test_creacion_parto_inseminacion(): void
    {

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->postJson($this->urlServicioInseminacion, $this->parto + ['personal_id'=>$this->veterinario->id]);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'parto',
                    fn (AssertableJson $json) =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'observacion' => 'string',
                        'cria' => 'array',
                        'cria.id' => 'integer',
                        'cria.nombre' => 'string',
                        'cria.numero' => 'integer',
                        'cria.sexo' => 'string',
                        'cria.origen' => 'string',
                        'cria.fecha_nacimiento' => 'string',
                    ])->has(
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


    public function test_obtener_parto_inseminacion(): void
    {
        $partos = $this->generarpartosInseminacion();

        $idRandom = rand(0, $this->cantidad_parto - 1);
        $idparto = $partos[$idRandom]->id;
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->getJson(sprintf($this->urlServicioInseminacion . '/%s', $idparto));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'parto',
                    fn (AssertableJson $json) =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'observacion' => 'string',
                        'cria' => 'array',
                        'cria.id' => 'integer',
                        'cria.nombre' => 'string',
                        'cria.numero' => 'integer',
                        'cria.sexo' => 'string',
                        'cria.origen' => 'string',
                        'cria.fecha_nacimiento' => 'string',
                    ])->has(
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

    public function test_actualizar_parto_inseminacion(): void
    {
        $partos = $this->generarpartosInseminacion();
        $idRandom = rand(0, $this->cantidad_parto - 1);
        $idpartoEditar = $partos[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->putJson(sprintf($this->urlServicioInseminacion . '/%s', $idpartoEditar), $this->parto + ['numero_toro' => $this->numero_toro]);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->where('parto.observacion', $this->parto['observacion'])
            ->has(
                'parto.veterinario',
                fn (AssertableJson $json)
                => $json->whereAllType(['id' => 'integer', 'nombre' => 'string'])
            )
                ->etc()
        );
    }


    public function test_eliminar_parto_inseminacion(): void
    {
        $partos = $this->generarpartosInseminacion();
        $idRandom = rand(0, $this->cantidad_parto - 1);
        $idToDelete = $partos[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->deleteJson(sprintf($this->urlServicioInseminacion . '/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['partoID' => $idToDelete]);
    }


    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_parto($parto, $errores): void
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

        Ganado::factory()
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->finca)
            ->create(['nombre' => 'test', 'numero' => 33]);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->postJson($this->urlServicioInseminacion, $parto);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_obtener_partos_de_todas_las_vacas(): void
    {
        /* partos con monta */
        Ganado::factory()
            ->count(5)
            ->hasPeso(1)
            ->hasServicios(7, ['servicioable_id' => $this->toro->id,'servicioable_type' => $this->toro->getMorphClass(), 'personal_id' => $this->veterinario->id])
            ->hasParto(3, function (array $attributes, Ganado $ganado) {
                $finca=$ganado->finca->id;
                $veterinario = Personal::factory()->create(['finca_id' => $finca, 'cargo_id' => 2]);
                $cria = Ganado::factory()->create(['finca_id' => $finca]);

                return ['partoable_id' => $ganado->servicioReciente->servicioable->id,'partoable_type' => $ganado->servicioReciente->servicioable->getMorphClass(), 'ganado_cria_id' => $cria->id, 'personal_id' => $veterinario->id];
            })
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->finca)
            ->create();

            /* partos con inseminacion */
        Ganado::factory()
            ->count(5)
            ->hasPeso(1)
            ->hasServicios(7, ['servicioable_id' => $this->pajuelaToro->id,'servicioable_type' => $this->pajuelaToro->getMorphClass(), 'personal_id' => $this->veterinario->id])
            ->hasParto(3, function (array $attributes, Ganado $ganado) {
                $finca=$ganado->finca->id;
                $veterinario = Personal::factory()->create(['finca_id' => $finca, 'cargo_id' => 2]);
                $cria = Ganado::factory()->create(['finca_id' => $finca]);

                return ['partoable_id' => $ganado->servicioReciente->servicioable->id,'partoable_type' => $ganado->servicioReciente->servicioable->getMorphClass(), 'ganado_cria_id' => $cria->id, 'personal_id' => $veterinario->id];
            })
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->finca)
            ->create();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->getJson(route('todosPartos'));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'todos_partos.1',
                    fn (AssertableJson $json) => $json->whereAllType([
                        'id' => 'integer',
                        'numero' => 'integer',
                        'ultimo_parto' => 'string',
                        'total_partos' => 'integer'
                    ])->has(
                        'toro',
                        fn (AssertableJson $json)
                        => $json->whereAllType([
                            'id' => 'integer',
                            'numero' => 'integer',
                        ])
                    )->has(
                        'cria',
                        fn (AssertableJson $json)
                        => $json->whereAllType([
                            'id' => 'integer',
                            'numero' => 'integer',
                        ])
                    )
            )->has(
                'todos_partos.6',
                fn (AssertableJson $json) => $json->whereAllType([
                    'id' => 'integer',
                    'numero' => 'integer',
                    'ultimo_parto' => 'string',
                    'total_partos' => 'integer'
                ])->has(
                    'pajuela_toro',
                    fn (AssertableJson $json)
                    => $json->whereAllType([
                        'id' => 'integer',
                        'codigo' => 'string',
                    ])
                )->has(
                    'cria',
                    fn (AssertableJson $json)
                    => $json->whereAllType([
                        'id' => 'integer',
                        'numero' => 'integer',
                    ])
                )
            )
            );
    }
}
