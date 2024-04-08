<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Ganado;
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
        'sexo' => 'H',
        'peso_nacimiento' => '33KG',


    ];

    private int $cantidad_parto = 10;

    private $user;
    private $ganado;
    private $toro;
    private $servicio;
    private $veterinario;
    private $estado;
    private $numero_toro;
    private $url;

    protected function setUp(): void
    {
        parent::setUp();

        $this->estado = Estado::all();

        $this->user
            = User::factory()->create();

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

        $this->veterinario
        = Personal::factory()
        ->for($this->user)
        ->create(['cargo_id' => 2]);

        $this->servicio = Servicio::factory()
            ->for($this->ganado)
            ->for($this->toro)
            ->create(['personal_id' => $this->veterinario]);


        $this->url = sprintf('api/ganado/%s/parto', $this->ganado->id);
    }

    private function generarpartos(): Collection
    {
        return Parto::factory()
            ->count($this->cantidad_parto)
            ->for($this->ganado)
            ->for(Ganado::factory()->for($this->user)->hasAttached($this->estado), 'ganado_cria')
            ->for($this->toro)
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
                [], ['observacion', 'nombre', 'sexo', 'peso_nacimiento']
            ],
            'caso de que exista el nombre o numero' => [
                [
                    'observacion',
                    'nombre' => 'test',
                    'numero' => 33,
                    'sexo' => 'H',
                    'tipo_id' => '4',
                    'peso_nacimiento' => '30KG',
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

    public function test_obtener_partos(): void
    {

        $this->generarpartos();

        $response = $this->actingAs($this->user)->getJson($this->url);
        
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


    public function test_creacion_parto(): void
    {

        $response = $this->actingAs($this->user)->postJson($this->url, $this->parto + ['personal_id'=>$this->veterinario->id]);

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


    public function test_obtener_parto(): void
    {
        $partos = $this->generarpartos();

        $idRandom = rand(0, $this->cantidad_parto - 1);
        $idparto = $partos[$idRandom]->id;
        $response = $this->actingAs($this->user)->getJson(sprintf($this->url . '/%s', $idparto));

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
    public function test_actualizar_parto(): void
    {
        $partos = $this->generarpartos();
        $idRandom = rand(0, $this->cantidad_parto - 1);
        $idpartoEditar = $partos[$idRandom]->id;

        $response = $this->actingAs($this->user)->putJson(sprintf($this->url . '/%s', $idpartoEditar), $this->parto + ['numero_toro' => $this->numero_toro]);

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

    public function test_obtener_partos_de_todas_las_vacas(): void
    {
        Ganado::factory()
            ->count(10)
            ->hasPeso(1)
            ->hasServicios(7, ['toro_id' => $this->toro->id, 'personal_id' => $this->veterinario->id])
            ->hasParto(3, function (array $attributes, Ganado $ganado) {
                $veterinario = Personal::factory()->create(['user_id' => $ganado->user_id, 'cargo_id' => 2]);
                $cria = Ganado::factory()->create(['user_id' => $ganado->user_id]);
                return ['toro_id' => $ganado->servicioReciente->toro_id, 'ganado_cria_id' => $cria->id, 'personal_id' => $veterinario->id];
            })
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->user)
            ->create();

        $response = $this->actingAs($this->user)->getJson(route('todosPartos'));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has('todos_partos.1', fn (AssertableJson $json) => $json->whereAllType([
                    'id' => 'integer',
                    'numero'=>'integer',
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
                
                )
            );
    }

    public function test_eliminar_parto(): void
    {
        $partos = $this->generarpartos();
        $idRandom = rand(0, $this->cantidad_parto - 1);
        $idToDelete = $partos[$idRandom]->id;


        $response = $this->actingAs($this->user)->deleteJson(sprintf($this->url . '/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['partoID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_parto($parto, $errores): void
    {
        //crear personal no veterinario
        Personal::factory()
            ->for($this->user)
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
            ->for($this->user)
            ->create(['nombre' => 'test', 'numero' => 33]);

        $response = $this->actingAs($this->user)->postJson($this->url, $parto);

        $response->assertStatus(422)->assertInvalid($errores);
    }
}
