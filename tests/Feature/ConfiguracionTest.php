<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\Common\NeedsUser;
use Tests\TestCase;

class ConfiguracionTest extends TestCase
{
    use RefreshDatabase;
    use NeedsUser;

    private array $configuracion = [
        'dias_diferencia_vacuna' => 10,
        'dias_evento_notificacion' => 10,
        'peso_servicio' => 10,
    ];

    private int $cantidad_configuracion = 10;

    public static function ErrorInputProvider(): array
    {
        return [
            'caso de insertar datos erróneos' => [
                [
                    'peso_servicio' => 0,
                    'dias_evento_notificacion' => 23243483384,
                    'dias_diferencia_vacuna' => 'ssss',
                ],
                [
                    'peso_servicio',
                    'dias_evento_notificacion',
                    'dias_diferencia_vacuna'
                ]
            ],
        ];
    }

    public function test_obtener_configuracion(): void
    {
        $this
            ->actingAs($this->user)
            ->getJson('api/configuracion')
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): AssertableJson => $json->whereAllType(
                    [
                        'configuracion.peso_servicio' => 'integer',
                        'configuracion.dias_evento_notificacion' => 'integer',
                        'configuracion.dias_diferencia_vacuna' => 'integer',
                    ]
                )
            );
    }

    private function setUpRequest(): static
    {
        $this
            ->actingAs($this->user)
            ->withSession($this->getSessionInitializationArray());

        return $this;
    }

    public function test_actualizar_configuracion(): void
    {
        $this
            ->setUpRequest()
            ->putJson(route('configuracion.update'), $this->configuracion)
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): AssertableJson => $json
                    ->where(
                        key: 'configuracion.peso_servicio',
                        expected: $this->configuracion['peso_servicio']
                    )
                    ->where(
                        key: 'configuracion.dias_evento_notificacion',
                        expected: $this->configuracion['dias_evento_notificacion']
                    )
                    ->where(
                        'configuracion.dias_diferencia_vacuna',
                        $this->configuracion['dias_diferencia_vacuna']
                    )
                    ->etc()
            )
            ->assertSessionHas(
                key: 'peso_servicio',
                value: $this->configuracion['peso_servicio']
            )
            ->assertSessionHas(
                key: 'dias_evento_notificacion',
                value: $this->configuracion['dias_evento_notificacion']
            )
            ->assertSessionHas(
                key: 'dias_diferencia_vacuna',
                value: $this->configuracion['dias_diferencia_vacuna']
            );;
    }

    /** @dataProvider ErrorinputProvider */
    public function test_error_validacion_registro_configuracion(array $configuracion, array $errores): void
    {
        $this
            ->actingAs($this->user)
            ->putJson('api/configuracion', $configuracion)
            ->assertStatus(422)
            ->assertInvalid($errores);
    }

    public function test_autorizacion_usuario_veterinario_maniupular_configuracion(): void
    {
        $usuarioVeterinario = User::factory()->hasConfiguracion()->create();
        $usuarioVeterinario->syncRoles('veterinario');

        $this
            ->cambiarRol($this->user)
            ->actingAs($usuarioVeterinario)
            ->putJson(route('configuracion.update'), $this->configuracion)
            ->assertStatus(403);
    }
}
