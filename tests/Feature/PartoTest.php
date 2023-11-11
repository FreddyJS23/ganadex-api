<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Ganado;
use App\Models\Parto;
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


        $this->servicio = Servicio::factory()
            ->for($this->ganado)
            ->for($this->toro)
            ->create();


        $this->url = sprintf('api/ganado/%s/parto', $this->ganado->id);
    }

    private function generarpartos(): Collection
    {
        return Parto::factory()
            ->count($this->cantidad_parto)
            ->for($this->ganado)
            ->for(Ganado::factory()->for($this->user)->hasAttached($this->estado), 'ganado_cria')
            ->for($this->toro)
            ->create();
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
            ->assertJson(fn (AssertableJson $json) => $json->has('partos', $this->cantidad_parto));
    }


    public function test_creacion_parto(): void
    {

        $response = $this->actingAs($this->user)->postJson($this->url, $this->parto);

        $response->assertStatus(201)->assertJson(['parto' => true]);
    }


    public function test_obtener_parto(): void
    {
        $partos = $this->generarpartos();

        $idRandom = rand(0, $this->cantidad_parto - 1);
        $idparto = $partos[$idRandom]->id;
        $response = $this->actingAs($this->user)->getJson(sprintf($this->url . '/%s', $idparto));

        $response->assertStatus(200)->assertJson(['parto' => true]);
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
                ->etc()
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
