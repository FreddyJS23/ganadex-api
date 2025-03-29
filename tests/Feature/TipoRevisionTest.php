<?php

namespace Tests\Feature;

use App\Models\TipoRevision;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\Common\NeedsUser;
use Tests\TestCase;

class TipoRevisionTest extends TestCase
{
    use RefreshDatabase;
    use NeedsUser;

    private array $tipo_revision = [
        'tipo' => 'enferma',
    ];
    private array $tipo_revision_actualizado = [
        'tipo' => 'envenenada',
    ];

    private int $cantidad_tipoRevision= 10;

    private function generarTipoRevision(): Collection
    {
        return TipoRevision::factory()
            ->count($this->cantidad_tipoRevision)
            ->create();
    }

    private function cambiarRol(User $user): void
    {
        $user->syncRoles('veterinario');
    }

    public static function ErrorInputProvider(): array
    {
        return [
            'caso de insertar datos erróneos' => [
                [
                    'tipo' => 'te',
                ],
                ['tipo']
            ],
            'caso de no insertar datos requeridos' => [
                [],
                ['tipo']
            ],
        ];
    }

    private function setUpRequest(): static
    {
        $this
            ->actingAs($this->user)
            ->withSession($this->getSessionInitializationArray());

        return $this;
    }

    public function test_obtener_tipo_revision(): void
    {
        $this->generarTipoRevision();

        $this
            ->setUpRequest()
            ->getJson(route('tipos_revision.index'))
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    key: 'tipos_revision',
                    length: $this->cantidad_tipoRevision + 4,
                    callback: fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType(
                        [
                            'id' => 'integer',
                            'tipo' => 'string'
                        ]
                    )
                )
            );
    }


    public function test_creacion_tipo_revision(): void
    {
        $this
            ->setUpRequest()
            ->postJson(route('tipos_revision.store'), $this->tipo_revision)
            ->assertStatus(201)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                    ->where(
                        key: 'tipo_revision.tipo',
                        expected: $this->tipo_revision['tipo']
                    )
                ->etc()
            );
    }



    public function test_actualizar_tipo_revision(): void
    {

        $tipoRevision= $this->generarTipoRevision();
        $idRandom = random_int(0, $this->cantidad_tipoRevision- 1);
        $idTipoRevisionEditar = $tipoRevision[$idRandom]->id;

        $this
            ->setUpRequest()
            ->putJson(route('tipos_revision.update',['tipos_revision'=>$idTipoRevisionEditar]), $this->tipo_revision_actualizado)
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                    ->where('tipo_revision.tipo', $this->tipo_revision_actualizado['tipo'])
                    ->etc()
            );
    }

    public function test_actualizar_tipo_revision_prederteminadas(): void
    {
        //los tipos de revision predertminadas no se pueden editar, las cuales son:gestacion, descarte y rutina
        $idRandom = random_int(1,3);

        $this
            ->setUpRequest()
            ->putJson(route('tipos_revision.update',['tipos_revision'=>$idRandom]), $this->tipo_revision_actualizado)
            ->assertStatus(403);
    }

    public function test_eliminar_tipo_revision(): void
    {
        $tipo_revision = $this->generarTipoRevision();
        $idRandom = random_int(0, $this->cantidad_tipoRevision- 1);
        $idToDelete = $tipo_revision[$idRandom]->id;


        $this
            ->setUpRequest()
            ->deleteJson(route('tipos_revision.destroy', ['tipos_revision' => $idToDelete]))
            ->assertStatus(200)
            ->assertJson(['tipoRevisionID' => $idToDelete]);
    }


    /** @dataProvider ErrorinputProvider */
    public function test_error_validacion_registro_tipo_revision(array $tipo_revision, array $errores): void
    {

        $this
            ->setUpRequest()
            ->postJson(route('tipos_revision.store'), $tipo_revision)
            ->assertStatus(422)
            ->assertInvalid($errores);
    }


    public function test_veterinario_no_autorizado_a_crear_tipo_revision(): void
    {
        $this->cambiarRol($this->user);

        $this
            ->setUpRequest()
            ->postJson(route('tipos_revision.store'), $this->tipo_revision)
            ->assertStatus(403);
    }

    public function test_veterinario_no_autorizado_a_actualizar_tipo_revision(): void
    {
        $this->cambiarRol($this->user);

        $tipo_revision = $this->generarTipoRevision();
        $idRandom = random_int(0, $this->cantidad_tipoRevision- 1);
        $idtipo_revisionEditar = $tipo_revision[$idRandom]->id;

        $this
            ->setUpRequest()
            ->putJson(
                uri: route('tipos_revision.update', ['tipos_revision' => $idtipo_revisionEditar]),
                data: $this->tipo_revision
            )
            ->assertStatus(403);
    }


    public function test_veterinario_no_autorizado_a_eliminar_tipo_revision(): void
    {
        $this->cambiarRol($this->user);

        $tipo_revision = $this->generarTipoRevision();
        $idRandom = random_int(0, $this->cantidad_tipoRevision- 1);
        $idToDelete = $tipo_revision[$idRandom]->id;

        $this
            ->setUpRequest()
            ->deleteJson(route('tipos_revision.destroy', ['tipos_revision' => $idToDelete]))
            ->assertStatus(403);
    }
}
