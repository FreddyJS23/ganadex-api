<?php

namespace Tests\Feature;

use App\Models\Comprador;
use App\Models\Finca;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class CompradorTest extends TestCase
{
    use RefreshDatabase;

    private array $comprador = [
        'nombre' => 'test',
    ];

    private int $cantidad_comprador = 10;

    private $user;
    private $finca;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();

            $this->user->assignRole('admin');

            $this->finca
            = Finca::factory()
            ->hasAttached($this->user)
            ->create();
    }

    private function generarComprador(): Collection
    {
        return Comprador::factory()
            ->count($this->cantidad_comprador)
            ->for($this->finca)
            ->create();
    }

    private function cambiarRol(User $user): void
    {
        $user->syncRoles('veterinario');
    }

    public static function ErrorInputProvider(): array
    {
        return [
            'caso de que exista el comprador' => [
                [
                    'nombre' => 'test',

                ], ['nombre']
            ],
            'caso de insertar datos erróneos' => [
                [
                    'nombre' => 'te',

                ], ['nombre']
            ],
            'caso de no insertar datos requeridos' => [
                [], ['nombre']
            ],
        ];
    }

    /**
     * A basic feature test example.
     */

    public function test_obtener_compradores(): void
    {
        $this->generarComprador();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->getJson('api/comprador');
        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'compradores',
                    $this->cantidad_comprador,
                    fn (AssertableJson $json) =>
                    $json->whereAllType(
                        [
                            'id' => 'integer', 'nombre' => 'string'
                        ]
                    )
                )
            );
    }


    public function test_creacion_comprador(): void
    {

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->postJson('api/comprador', $this->comprador);

        $response->assertStatus(201)->assertJson(
            fn (AssertableJson $json) =>
            $json->where('comprador.nombre',$this->comprador['nombre'])
            ->etc()
        );
    }


    public function test_obtener_comprador(): void
    {
        $comprador = $this->generarComprador();
        $idRandom = rand(0, $this->cantidad_comprador - 1);
        $idComprador = $comprador[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->getJson(sprintf('api/comprador/%s', $idComprador));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->whereAllType(
                [
                    'comprador.id' => 'integer', 'comprador.nombre' => 'string'
                ]
            )
        );
    }
    public function test_actualizar_comprador(): void
    {
        $comprador = $this->generarComprador();
        $idRandom = rand(0, $this->cantidad_comprador - 1);
        $idCompradorEditar = $comprador[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->putJson(sprintf('api/comprador/%s', $idCompradorEditar), $this->comprador);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->where('comprador.nombre', $this->comprador['nombre'])
            ->etc()
        );
    }

    public function test_actualizar_comprador_con_otro_existente_repitiendo_campos_unicos(): void
    {
        $compradorExistente = Comprador::factory()->for($this->finca)->create(['nombre' => 'test']);

        $comprador = $this->generarComprador();
        $idRandom = rand(0, $this->cantidad_comprador - 1);
        $idCompradorEditar = $comprador[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->putJson(sprintf('api/comprador/%s', $idCompradorEditar), $this->comprador);

        $response->assertStatus(422)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['errors.nombre'])
            ->etc());
    }

    public function test_actualizar_comprador_conservando_campos_unicos(): void
    {
        $compradorExistente = Comprador::factory()->for($this->finca)->create(['nombre' => 'test']);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->putJson(sprintf('api/comprador/%s', $compradorExistente->id), $this->comprador);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->where('comprador.nombre', $this->comprador['nombre'])
            ->etc()
        );
    }

    public function test_eliminar_comprador(): void
    {
        $comprador = $this->generarComprador();
        $idRandom = rand(0, $this->cantidad_comprador - 1);
        $idToDelete = $comprador[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->deleteJson(sprintf('api/comprador/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['compradorID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_comprador($comprador, $errores): void
    {
        Comprador::factory()->for($this->finca)->create(['nombre' => 'test']);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->postJson('api/comprador', $comprador);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_autorizacion_maniupular__comprador_otro_usuario(): void
    {
        $this->cambiarRol($this->user);

        $otroFinca = Finca::factory()
        ->hasAttached($this->user)
        ->create(['nombre' => 'otro_finca']);

        $compradorOtroUsuario = Comprador::factory()->for($otroFinca)->create();

        $idCompradorOtroUsuario = $compradorOtroUsuario->id;

        $this->generarComprador();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->putJson(sprintf('api/comprador/%s', $idCompradorOtroUsuario), $this->comprador);

        $response->assertStatus(403);
    }


    public function test_veterinario_no_autorizado_a_crear_comprador(): void
    {
        $this->cambiarRol($this->user);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->postJson('api/comprador', $this->comprador);

        $response->assertStatus(403);
    }

    public function test_veterinario_no_autorizado_a_actualizar_comprador(): void
    {
        $this->cambiarRol($this->user);

        $compradores = $this->generarComprador();
        $idRandom = rand(0, $this->cantidad_comprador - 1);
        $idCompradorEditar = $compradores[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->putJson(sprintf('api/comprador/%s', $idCompradorEditar), $this->comprador);

        $response->assertStatus(403);
    }


    public function test_veterinario_no_autorizado_a_eliminar_comprador(): void
    {
        $this->cambiarRol($this->user);

        $compradores = $this->generarComprador();
        $idRandom = rand(0, $this->cantidad_comprador - 1);
        $idToDelete = $compradores[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->deleteJson(sprintf('api/comprador/%s', $idToDelete));

        $response->assertStatus(403);
    }
}
