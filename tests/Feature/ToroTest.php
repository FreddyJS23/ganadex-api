<?php

namespace Tests\Feature;

use App\Models\Ganado;
use App\Models\Toro;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class ToroTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    private array $toro = [
        'nombre' => 'test',
        'numero' => 392,
        'origen' => 'local',
        'sexo' => 'M',
        'tipo_id' => 4,
        'fecha_nacimiento' => '2015-02-17',

    ];

    private int $cantidad_toro = 10;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();
    }

    private function generarToros(): Collection
    {
        return Toro::factory()
            ->count(10)
            ->for($this->user)
            ->for(Ganado::factory()->for($this->user))->create();
    }
    public static function ErrorInputProvider(): array
    {
        return [
            'caso de que exista el nombre o numero' => [
                [
                    'nombre' => 'test',
                    'numero' => 300,
                    'origen' => 'local',
                    'sexo' => 'M',
                    'tipo_id' => '4',
                    'fecha_nacimiento' => '2015-03-02',
                ], ['nombre', 'numero']
            ],
            'caso de insertar datos erróneos' => [
                [
                    'nombre' => 'te',
                    'numero' => 'hj',
                    'origen' => 'ce',
                    'fecha_nacimiento' => '2015-13-02',
                ], [
                    'nombre', 'numero', 'origen', 'fecha_nacimiento',
                ]
            ],
            'caso de no insertar datos requeridos' => [
                ['origen' => 'local'], ['nombre', 'numero',]
            ],
        ];
    }



    /**
     * A basic feature test example.
     */

    public function test_obtener_toros(): void
    {
        $this->generarToros();

        $response = $this->actingAs($this->user)->getJson('api/toro');
        $response->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->has('toros', $this->cantidad_toro));
    }


    public function test_creacion_toro(): void
    {

        $response = $this->actingAs($this->user)->postJson('api/toro', $this->toro);

        $response->assertStatus(201)->assertJson(['toro' => true]);
    }


    public function test_obtener_toro(): void
    {
        $toros = $this->generarToros();
        $idRandom = rand(0, $this->cantidad_toro - 1);
        $idToro = $toros[$idRandom]->id;


        $response = $this->actingAs($this->user)->getJson(sprintf('api/toro/%s', $idToro));

        $response->assertStatus(200)->assertJson(['toro' => true]);
    }
    public function test_actualizar_toro(): void
    {
        $toros = $this->generarToros();
        $idRandom = rand(0, $this->cantidad_toro - 1);
        $idToroEditar = $toros[$idRandom]->id;

        $response = $this->actingAs($this->user)->putJson(sprintf('api/toro/%s', $idToroEditar), $this->toro);

        $response->assertStatus(200)->assertJson(['toro' => true]);
    }

    public function test_eliminar_toro(): void
    {
        $toros = $this->generarToros();
        $idRandom = rand(0, $this->cantidad_toro - 1);
        $idToDelete = $toros[$idRandom]->id;


        $response = $this->actingAs($this->user)->deleteJson(sprintf('api/toro/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['toroID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_toro($toro, $errores): void
    {
        Toro::factory()
            ->for($this->user)
            ->for(Ganado::factory()->for($this->user)->create(['nombre' => 'test', 'numero' => 300]))
            ->create();

        $response = $this->actingAs($this->user)->postJson('api/toro', $toro);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_autorizacion_maniupular__toro_otro_usuario(): void
    {
        $otroUsuario = User::factory()->create();

        $toroOtroUsuario = Toro::factory()
            ->for($otroUsuario)
            ->for(Ganado::factory()->for($otroUsuario))
            ->create();

        $idToroOtroUsuario = $toroOtroUsuario->id;

        $this->generarToros();

        $response = $this->actingAs($this->user)->putJson(sprintf('api/toro/%s', $idToroOtroUsuario), $this->toro);

        $response->assertStatus(403);
    }
}
