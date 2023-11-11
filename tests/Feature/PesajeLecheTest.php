<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Ganado;
use App\Models\Leche;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class PesajeLecheTest extends TestCase
{
    use RefreshDatabase;

    private array $pesoLeche = [
        'peso_leche' => '30',

    ];

    private int $cantidad_pesoLeche = 10;

    private $user;
    private $ganado;
    private $estado;
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

        $this->url = sprintf('api/ganado/%s/pesaje_leche', $this->ganado->id);
    }

    private function generarPesajesLeche(): Collection
    {
        return Leche::factory()
            ->count($this->cantidad_pesoLeche)
            ->for($this->ganado)
            ->for($this->user)
            ->create();
    }
    public static function ErrorInputProvider(): array
    {
        return [

            'caso de insertar datos erróneos' => [
                [
                    'peso_leche' => 'dd33'
                ], ['peso_leche']
            ],
            'caso de no insertar datos requeridos' => [
                [], ['peso_leche']
            ],
        ];
    }



    /**
     * A basic feature test example.
     */

    public function test_obtener_todos_pesaje_de_leches(): void
    {
        $this->generarPesajesLeche();

        $response = $this->actingAs($this->user)->getJson($this->url);
        $response->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->has('pesajes_leche', $this->cantidad_pesoLeche));
    }


    public function test_creacion_pesaje_leche(): void
    {

        $response = $this->actingAs($this->user)->postJson($this->url, $this->pesoLeche);

        $response->assertStatus(201)->assertJson(['pesaje_leche' => true]);
    }


    public function test_obtener_pesaje_leche(): void
    {
        $pesajesDeLeche = $this->generarPesajesLeche();

        $idRandom = rand(0, $this->cantidad_pesoLeche - 1);
        $idPesoLeche = $pesajesDeLeche[$idRandom]->id;
        $response = $this->actingAs($this->user)->getJson(sprintf($this->url . '/%s', $idPesoLeche));

        $response->assertStatus(200)->assertJson(['pesaje_leche' => true]);
    }
    public function test_actualizar_pesoLeche(): void
    {
        $pesajesDeLeche = $this->generarPesajesLeche();
        $idRandom = rand(0, $this->cantidad_pesoLeche - 1);
        $idPesoLecheEditar = $pesajesDeLeche[$idRandom]->id;

        $response = $this->actingAs($this->user)->putJson(sprintf($this->url . '/%s', $idPesoLecheEditar), $this->pesoLeche);

        $response->assertStatus(200)->assertJson(['pesaje_leche' => true]);
    }

    public function test_eliminar_pesoLeche(): void
    {
        $pesajesDeLeche = $this->generarPesajesLeche();
        $idRandom = rand(0, $this->cantidad_pesoLeche - 1);
        $idToDelete = $pesajesDeLeche[$idRandom]->id;


        $response = $this->actingAs($this->user)->deleteJson(sprintf($this->url . '/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['pesajeLecheID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_pesoLeche($pesoLeche, $errores): void
    {

        $response = $this->actingAs($this->user)->postJson($this->url, $pesoLeche);

        $response->assertStatus(422)->assertInvalid($errores);
    }
}
