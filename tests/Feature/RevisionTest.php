<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Ganado;
use App\Models\Revision;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class RevisionTest extends TestCase
{
    use RefreshDatabase;

    private array $revision = [
        'diagnostico' => 'revisar',
        'tratamiento' => 'medicina',


    ];

    private int $cantidad_revision = 10;

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

        $this->url = sprintf('api/ganado/%s/revision', $this->ganado->id);
    }

    private function generarRevision(): Collection
    {
        return Revision::factory()
            ->count($this->cantidad_revision)
            ->for($this->ganado)
            ->create();
    }
    public static function ErrorInputProvider(): array
    {
        return [

            'caso de insertar datos erróneos' => [
                [
                    'diagnostico' => 'te',
                    'tratamiento' => 'hj',
                ], ['diagnostico', 'tratamiento']
            ],
            'caso de no insertar datos requeridos' => [
                [], ['diagnostico', 'tratamiento']
            ],
        ];
    }



    /**
     * A basic feature test example.
     */

    public function test_obtener_revisiones(): void
    {
        $this->generarRevision();

        $response = $this->actingAs($this->user)->getJson($this->url);
        $response->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->has('revisiones', $this->cantidad_revision));
    }


    public function test_creacion_revision(): void
    {

        $response = $this->actingAs($this->user)->postJson($this->url, $this->revision);

        $response->assertStatus(201)->assertJson(['revision' => true]);
    }


    public function test_obtener_revision(): void
    {
        $revisiones = $this->generarRevision();

        $idRandom = rand(0, $this->cantidad_revision - 1);
        $idRevision = $revisiones[$idRandom]->id;
        $response = $this->actingAs($this->user)->getJson(sprintf($this->url . '/%s', $idRevision));

        $response->assertStatus(200)->assertJson(['revision' => true]);
    }
    public function test_actualizar_revision(): void
    {
        $revisiones = $this->generarRevision();
        $idRandom = rand(0, $this->cantidad_revision - 1);
        $idRevisionEditar = $revisiones[$idRandom]->id;

        $response = $this->actingAs($this->user)->putJson(sprintf($this->url . '/%s', $idRevisionEditar), $this->revision);

        $response->assertStatus(200)->assertJson(['revision' => true]);
    }

    public function test_eliminar_revision(): void
    {
        $revisiones = $this->generarRevision();
        $idRandom = rand(0, $this->cantidad_revision - 1);
        $idToDelete = $revisiones[$idRandom]->id;


        $response = $this->actingAs($this->user)->deleteJson(sprintf($this->url . '/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['revisionID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_revision($revision, $errores): void
    {

        $response = $this->actingAs($this->user)->postJson($this->url, $revision);

        $response->assertStatus(422)->assertInvalid($errores);
    }
}
