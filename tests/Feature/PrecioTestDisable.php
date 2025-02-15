<?php

namespace Tests\Feature;

use App\Models\Precio;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class PrecioTest extends TestCase
{
    use RefreshDatabase;

    private array $precio = [
        'precio' => 30,
        'fecha' => '2020-10-02',

    ];

    private int $cantidad_precio = 10;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->hasConfiguracion()->create();
    }

    private function generarPrecio(): Collection
    {
        return Precio::factory()
            ->count($this->cantidad_precio)
            ->for($this->user)
            ->create();
    }
    public static function ErrorInputProvider(): array
    {
        return [
            'caso de insertar datos erróneos' => [
                [
                    'precio' => 'd32',
                ], ['precio']
            ],
            'caso de no insertar datos requeridos' => [
                [], ['precio']
            ],
        ];
    }

    /**
     * A basic feature test example.
     */

    public function test_obtener_precios(): void
    {
        $this->generarPrecio();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson('api/precio');

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'precios',
                    $this->cantidad_precio,
                    fn (AssertableJson $json) =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'precio' => 'integer|double',
                        'fecha' => 'string',
                    ])
                )
            );
    }


    public function test_creacion_precio(): void
    {

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson('api/precio', $this->precio);

        $response->assertStatus(201)->assertJson(
            fn (AssertableJson $json) =>
            $json->first(
                fn (AssertableJson $json) =>
                $json->whereAllType([
                    'precio' => 'integer|double',
                    'fecha' => 'string'
                ])->etc()
            )
        );
    }


    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_precio($precio, $errores): void
    {
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson('api/precio', $precio);

        $response->assertStatus(422)->assertInvalid($errores);
    }
}
