<?php

namespace Tests\Feature;

use App\Models\Configuracion;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class ConfiguracionTest extends TestCase
{
    use RefreshDatabase;

    private array $configuracion = [
        'dias_diferencia_vacuna' => 10,
        'dias_evento_notificacion' => 10,
        'peso_servicio' => 10,
    ];

    private int $cantidad_configuracion = 10;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->hasConfiguracion()->create();

        $this->user->assignRole('admin');
    }

    public static function ErrorInputProvider(): array
    {
        return [

            'caso de insertar datos erróneos' => [
                [
                'peso_servicio' => 0,
                'dias_evento_notificacion' => 23243483384,
                'dias_diferencia_vacuna' => 'ssss',
                ], ['peso_servicio', 'dias_evento_notificacion', 'dias_diferencia_vacuna']
            ],
        ];
    }



    /**
     * A basic feature test example.
     */

    public function test_obtener_configuracion(): void
    {

        $response = $this->actingAs($this->user)->getJson('api/configuracion');

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->whereAllType(
                [
                    'configuracion.id' => 'integer',
                    'configuracion.peso_servicio' => 'integer',
                    'configuracion.dias_evento_notificacion' => 'integer',
                    'configuracion.dias_diferencia_vacuna' => 'integer',
                ]
            )
        );
    }


    public function test_actualizar_configuracion(): void
    {
        $response = $this->actingAs($this->user)->putJson(route('configuracion.update'),$this->configuracion);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->where('configuracion.peso_servicio',$this->configuracion['peso_servicio'])
            ->where('configuracion.dias_evento_notificacion',$this->configuracion['dias_evento_notificacion'])
            ->where('configuracion.dias_diferencia_vacuna',$this->configuracion['dias_diferencia_vacuna'])
            ->etc()
        );
    }


    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_configuracion($configuracion, $errores): void
    {

        $response = $this->actingAs($this->user)->putJson('api/configuracion', $configuracion);

        $response->assertStatus(422)->assertInvalid($errores);
    }


    public function test_autorizacion_usuario_veterinario_maniupular_configuracion(): void
    {
        $usuarioVeterinario = User::factory()->hasConfiguracion()->create();

        $usuarioVeterinario->syncRoles('veterinario');

        $response = $this->actingAs($usuarioVeterinario)->putJson(route('configuracion.update'),$this->configuracion);

        $response->assertStatus(403);
    }
}
