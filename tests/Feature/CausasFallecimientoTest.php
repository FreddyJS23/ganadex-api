<?php

namespace Tests\Feature;

use App\Models\CausasFallecimiento;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\Common\NeedsCasusaFallecimiento;
use Tests\Feature\Common\NeedsUser;
use Tests\TestCase;

class CausasFallecimientoTest extends TestCase
{
    use RefreshDatabase;
    use NeedsUser;

    private array $causa_fallecimiento = [
        'causa' => 'enferma',
    ];
    private array $causa_fallecimiento_actualizado = [
        'causa' => 'envenenada',
    ];

    private int $cantidad_causaFallecimiento = 10;

    private function generarCausasFallecimiento(): Collection
    {
        return CausasFallecimiento::factory()
            ->count($this->cantidad_causaFallecimiento)
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
                    'causa' => 'te',
                ],
                ['causa']
            ],
            'caso de no insertar datos requeridos' => [
                [],
                ['causa']
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

    public function test_obtener_causas_fallecimientos(): void
    {
        $this->generarCausasFallecimiento();

        $this
            ->setUpRequest()
            ->getJson(route('causas_fallecimiento.index'))
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json) => $json->has(
                    key: 'causas_fallecimiento',
                    length: $this->cantidad_causaFallecimiento,
                    callback: fn(AssertableJson $json) => $json->whereAllType(
                        [
                            'id' => 'integer',
                            'causa' => 'string'
                        ]
                    )
                )
            );
    }


    public function test_creacion_causa_fallecimiento(): void
    {
        $this
            ->setUpRequest()
            ->postJson(route('causas_fallecimiento.store'), $this->causa_fallecimiento)
            ->assertStatus(201)
            ->assertJson(
                fn(AssertableJson $json) => $json
                    ->where(
                        key: 'causa_fallecimiento.causa',
                        expected: $this->causa_fallecimiento['causa']
                    )
                ->etc()
            );
    }



    public function test_actualizar_causa_fallecimiento(): void
    {

        $causaFallecimiento = $this->generarCausasFallecimiento();
        $idRandom = random_int(0, $this->cantidad_causaFallecimiento - 1);
        $idcausaFallecimientoEditar = $causaFallecimiento[$idRandom]->id;

        $this
            ->setUpRequest()
            ->putJson(route('causas_fallecimiento.update',['causas_fallecimiento'=>$idcausaFallecimientoEditar]), $this->causa_fallecimiento_actualizado)
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json) => $json
                    ->where('causa_fallecimiento.causa', $this->causa_fallecimiento_actualizado['causa'])
                    ->etc()
            );
    }

    public function test_eliminar_causa_fallecimiento(): void
    {
        $causa_fallecimiento = $this->generarCausasFallecimiento();
        $idRandom = random_int(0, $this->cantidad_causaFallecimiento- 1);
        $idToDelete = $causa_fallecimiento[$idRandom]->id;


        $this
            ->setUpRequest()
            ->deleteJson(route('causas_fallecimiento.destroy', ['causas_fallecimiento' => $idToDelete]))
            ->assertStatus(200)
            ->assertJson(['causaFallecimientoID' => $idToDelete]);
    }


    /** @dataProvider ErrorinputProvider */
    public function test_error_validacion_registro_causa_fallecimiento($causa_fallecimiento, $errores): void
    {

        $this
            ->setUpRequest()
            ->postJson(route('causas_fallecimiento.store'), $causa_fallecimiento)
            ->assertStatus(422)
            ->assertInvalid($errores);
    }


    public function test_veterinario_no_autorizado_a_crear_causa_fallecimiento(): void
    {
        $this->cambiarRol($this->user);

        $this
            ->setUpRequest()
            ->postJson(route('causas_fallecimiento.store'), $this->causa_fallecimiento)
            ->assertStatus(403);
    }

    public function test_veterinario_no_autorizado_a_actualizar_causa_fallecimiento(): void
    {
        $this->cambiarRol($this->user);

        $causa_fallecimiento = $this->generarCausasFallecimiento();
        $idRandom = random_int(0, $this->cantidad_causaFallecimiento - 1);
        $idcausa_fallecimientoEditar = $causa_fallecimiento[$idRandom]->id;

        $this
            ->setUpRequest()
            ->putJson(
                uri: route('causas_fallecimiento.update', ['causas_fallecimiento' => $idcausa_fallecimientoEditar]),
                data: $this->causa_fallecimiento
            )
            ->assertStatus(403);
    }


    public function test_veterinario_no_autorizado_a_eliminar_causa_fallecimiento(): void
    {
        $this->cambiarRol($this->user);

        $causa_fallecimiento = $this->generarCausasFallecimiento();
        $idRandom = random_int(0, $this->cantidad_causaFallecimiento - 1);
        $idToDelete = $causa_fallecimiento[$idRandom]->id;

        $this
            ->setUpRequest()
            ->deleteJson(route('causas_fallecimiento.destroy', ['causas_fallecimiento' => $idToDelete]))
            ->assertStatus(403);
    }
}
