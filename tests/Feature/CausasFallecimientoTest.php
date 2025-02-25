<?php

namespace Tests\Feature;

use App\Models\CausasFallecimiento;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\Common\NeedsUser;
use Tests\TestCase;

class CausasFallecimientoTest extends TestCase
{
    use RefreshDatabase;
    use NeedsUser;

    private array $causa_fallecimiento = ['causa' => 'enferma'];
    private array $causa_fallecimiento_actualizado = ['causa' => 'envenenada'];
    private int $cantidad_causaFallecimiento = 10;

    private function generarCausasFallecimiento(): Collection
    {
        return CausasFallecimiento::factory()
            ->count($this->cantidad_causaFallecimiento)
            ->create();
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
                fn(AssertableJson $json): AssertableJson => $json->has(
                    key: 'causas_fallecimiento',
                    length: $this->cantidad_causaFallecimiento,
                    callback: fn(AssertableJson $json): AssertableJson => $json->whereAllType(
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
                fn(AssertableJson $json): AssertableJson => $json
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
            ->putJson(
                uri: route(
                    name: 'causas_fallecimiento.update',
                    parameters: [
                        'causas_fallecimiento' => $idcausaFallecimientoEditar
                    ]
                ),
                data: $this->causa_fallecimiento_actualizado
            )
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): AssertableJson => $json
                    ->where(
                        key: 'causa_fallecimiento.causa',
                        expected: $this->causa_fallecimiento_actualizado['causa']
                    )
                    ->etc()
            );
    }

    public function test_eliminar_causa_fallecimiento(): void
    {
        $causa_fallecimiento = $this->generarCausasFallecimiento();
        $idRandom = random_int(0, $this->cantidad_causaFallecimiento - 1);
        $idToDelete = $causa_fallecimiento[$idRandom]->id;

        $this
            ->setUpRequest()
            ->deleteJson(route(
                name: 'causas_fallecimiento.destroy',
                parameters: ['causas_fallecimiento' => $idToDelete]
            ))
            ->assertStatus(200)
            ->assertJson(['causaFallecimientoID' => $idToDelete]);
    }


    /** @dataProvider ErrorinputProvider */
    public function test_error_validacion_registro_causa_fallecimiento(
        array $causa_fallecimiento,
        string|array|null $errores
    ): void {
        $this
            ->setUpRequest()
            ->postJson(
                uri: route('causas_fallecimiento.store'),
                data: $causa_fallecimiento
            )
            ->assertStatus(422)
            ->assertInvalid($errores);
    }

    public function test_veterinario_no_autorizado_a_crear_causa_fallecimiento(): void
    {
        $this
            ->cambiarRol($this->user)
            ->setUpRequest()
            ->postJson(route('causas_fallecimiento.store'), $this->causa_fallecimiento)
            ->assertStatus(403);
    }

    public function test_veterinario_no_autorizado_a_actualizar_causa_fallecimiento(): void
    {
        $causa_fallecimiento = $this->generarCausasFallecimiento();
        $idRandom = random_int(0, $this->cantidad_causaFallecimiento - 1);
        $idcausa_fallecimientoEditar = $causa_fallecimiento[$idRandom]->id;

        $this
            ->cambiarRol($this->user)
            ->setUpRequest()
            ->putJson(
                uri: route(
                    name: 'causas_fallecimiento.update',
                    parameters: [
                        'causas_fallecimiento' => $idcausa_fallecimientoEditar
                    ]
                ),
                data: $this->causa_fallecimiento
            )
            ->assertStatus(403);
    }

    public function test_veterinario_no_autorizado_a_eliminar_causa_fallecimiento(): void
    {
        $causa_fallecimiento = $this->generarCausasFallecimiento();
        $idRandom = random_int(0, $this->cantidad_causaFallecimiento - 1);
        $idToDelete = $causa_fallecimiento[$idRandom]->id;

        $this
            ->cambiarRol($this->user)
            ->setUpRequest()
            ->deleteJson(route(
                name: 'causas_fallecimiento.destroy',
                parameters: ['causas_fallecimiento' => $idToDelete]
            ))
            ->assertStatus(403);
    }
}
