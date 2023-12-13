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
        'dark_mode' => true,
        'moneda' => '$',
    ];

    private int $cantidad_configuracion = 10;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();
    }

    private function generarConfiguracion(): Configuracion
    {
        return Configuracion::factory()
            ->for($this->user)
            ->create();
    }
    public static function ErrorInputProvider(): array
    {
        return [

            'caso de insertar datos erróneos' => [
                [
                    'dark_mode' => 'te',
                    'moneda' => 1,

                ], ['dark_mode', 'moneda']
            ],
            'caso de no insertar datos requeridos' => [
                [], ['dark_mode', 'moneda']
            ],
        ];
    }



    /**
     * A basic feature test example.
     */

    public function test_obtener_configuracion(): void
    {
        $this->generarConfiguracion();

        $response = $this->actingAs($this->user)->getJson('api/configuracion');

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->whereAllType(
                [
                    'configuracion.id' => 'integer',
                    'configuracion.dark_mode' => 'boolean',
                    'configuracion.moneda' => 'string'
                ]
            )
        );
    }

    public function test_usuario_no_tiene_configuracion(): void
    {

        $response = $this->actingAs($this->user)->getJson('api/configuracion');

        $response->assertStatus(404)->assertJson(['configuracion' => '']);
    }


    public function test_creacion_configuracion(): void
    {

        $response = $this->actingAs($this->user)->postJson('api/configuracion', $this->configuracion);

        $response->assertStatus(201)->assertJson(['configuracion' => true]);
    }


    public function test_actualizar_configuracion(): void
    {
        $configuracion = $this->generarConfiguracion();
        $idConfiguracionEditar = $configuracion->id;

        $response = $this->actingAs($this->user)->putJson(sprintf('api/configuracion/%s', $idConfiguracionEditar), $this->configuracion);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->where('configuracion.dark_mode',$this->configuracion['dark_mode'])
            ->where('configuracion.moneda', $this->configuracion['moneda'])
            ->etc()
        );
    }


    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_configuracion($configuracion, $errores): void
    {

        $response = $this->actingAs($this->user)->postJson('api/configuracion', $configuracion);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_autorizacion_maniupular__configuracion_otro_usuario(): void
    {
        $otroUsuario = User::factory()->create();

        $configuracionOtroUsuario = configuracion::factory()->for($otroUsuario)->create();

        $idConfiguracionOtroUsuario = $configuracionOtroUsuario->id;

        $this->generarConfiguracion();

        $response = $this->actingAs($this->user)->putJson(sprintf('api/configuracion/%s', $idConfiguracionOtroUsuario), $this->configuracion);

        $response->assertStatus(403);
    }
}
