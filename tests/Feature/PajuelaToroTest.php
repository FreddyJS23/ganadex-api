<?php

namespace Tests\Feature;

use App\Models\PajuelaToro;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class PajuelaToroTest extends TestCase
{
    use RefreshDatabase;

    private array $pajuela_toro = [
        'codigo' => '21DDSQ7',
    ];

    private int $cantidad_pajuelaToro = 10;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();
    }

    private function generarPersonal(): Collection
    {
        return PajuelaToro::factory()
            ->count($this->cantidad_pajuelaToro)
            ->for($this->user)
            ->create();
    }
    public static function ErrorInputProvider(): array
    {
        return [
            'caso de insertar datos erróneos' => [
                [
                    'codigo' => 33284,
                ], ['codigo',]
            ],
            'caso de no insertar datos requeridos' => [
                [],
                ['codigo',]
            ],
        ];
    }


    /**
     * A basic feature test example.
     */

    public function test_obtener_todo_pajuelas_toro(): void
    {
        $this->generarPersonal();

        $response = $this->actingAs($this->user)->getJson(route('pajuela_toros.index'));
        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'pajuela_toros',
                    $this->cantidad_pajuelaToro,
                    fn (AssertableJson $json) => $json->whereAllType([
                        'id' => 'integer',
                        'codigo' => 'string',
                    ])
                )
            );
    }


    public function test_creacion_pajuela_toro(): void
    {

        $response = $this->actingAs($this->user)->postJson(route('pajuela_toros.store'), $this->pajuela_toro);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'pajuela_toro',
                    fn (AssertableJson $json) => $json->whereAllType([
                        'id' => 'integer',
                        'codigo' => 'string',
                    ])
                )
            );
    }


    public function test_obtener_pajuela_toro(): void
    {
        $pajuela_torols = $this->generarPersonal();
        $idRandom = rand(0, $this->cantidad_pajuelaToro - 1);
        $idPajuelaToro = $pajuela_torols[$idRandom]->id;

        $response = $this->actingAs($this->user)->getJson(route('pajuela_toros.show',['pajuela_toro'=>$idPajuelaToro]));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'pajuela_toro',
                    fn (AssertableJson $json) => $json->whereAllType([
                        'id' => 'integer',
                        'codigo' => 'string',
                    ])
                )
            );
    }

    public function test_actualizar_pajuela_toro(): void
    {
        $pajuela_toro = $this->generarPersonal();
        $idRandom = rand(0, $this->cantidad_pajuelaToro - 1);
        $idPajuelaToroEditar = $pajuela_toro[$idRandom]->id;

        $response = $this->actingAs($this->user)->putJson(route('pajuela_toros.update',['pajuela_toro'=>$idPajuelaToroEditar]), $this->pajuela_toro);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'pajuela_toro',
                    fn (AssertableJson $json) => $json
                        ->where('codigo', $this->pajuela_toro['codigo'])
                        ->etc()
                )
            );
    }


    public function test_eliminar_personal(): void
    {
        $pajuela_toro = $this->generarPersonal();
        $idRandom = rand(0, $this->cantidad_pajuelaToro - 1);
        $idToDelete = $pajuela_toro[$idRandom]->id;


        $response = $this->actingAs($this->user)->deleteJson(route('pajuela_toros.destroy',['pajuela_toro'=>$idToDelete]));

        $response->assertStatus(200)->assertJson(['pajuela_toroID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_personal($pajuela_toro, $errores): void
    {
        PajuelaToro::factory()->for($this->user)->create(['codigo' => 28472738]);

        $response = $this->actingAs($this->user)->postJson(route('pajuela_toros.store'), $pajuela_toro);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_autorizacion_maniupular__personal_otro_usuario(): void
    {
        $otroUsuario = User::factory()->create();

        $pajuela_torolOtroUsuario = PajuelaToro::factory()->for($otroUsuario)->create();

        $idPersonalOtroUsuario = $pajuela_torolOtroUsuario->id;

        $this->generarPersonal();

        $response = $this->actingAs($this->user)->putJson(route('pajuela_toros.update',['pajuela_toro'=>$idPersonalOtroUsuario]), $this->pajuela_toro);

        $response->assertStatus(403);
    }
}
