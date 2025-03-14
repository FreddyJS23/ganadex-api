<?php

namespace Tests\Feature;

use App\Models\Precio;
use App\Models\User;
use App\Models\VentaLeche;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class VentaLecheTest extends TestCase
{
    use RefreshDatabase;

    private array $ventaLeche = [
        'cantidad' => 33,
    ];

    private $precio;

    private int $cantidad_ventaLeche = 10;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->hasConfiguracion()->create();

        $this->precio = Precio::factory()->for($this->user)->create();
    }

    private function generarVentaLeche(): Collection
    {
        return VentaLeche::factory()
            ->count($this->cantidad_ventaLeche)
            ->for(Precio::factory()->for($this->user))
            ->for($this->user)
            ->create();
    }
    public static function ErrorInputProvider(): array
    {
        return [
            'caso de insertar datos erróneos' => [
                [
                    'cantidad' => 'd32',
                ], ['cantidad']
            ],
            'caso de no insertar datos requeridos' => [
                [], ['precio_id', 'cantidad']
            ],

            'caso de insertar precio inexistente' => [
                [
                    'precio_' => 0,
                    'fecha' => '2020-02-02',
                    'cantidad' => 33,
                ], ['precio_id']
            ],
        ];
    }

    /**
     * A basic feature test example.
     */

    public function test_obtener_ventas_de_leche(): void
    {
        $this->generarVentaLeche();

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson('api/venta_leche');
        $response->assertStatus(200)
            ->assertJson(fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has('ventas_de_leche', $this->cantidad_ventaLeche, fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                ->whereAllType([
                    'id' => 'integer',
                    'fecha' => 'string',
                    'cantidad' => 'string',
                    'precio' => 'integer|double',
                ])));
    }


    public function test_creacion_venta_de_leche(): void
    {

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson('api/venta_leche', $this->ventaLeche + ['precio_id' => $this->precio->id]);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                $json->has(
                    'venta_leche',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'cantidad' => 'string',
                        'precio' => 'integer|double'
                    ])
                )
            );
    }


    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_venta_de_leche(array $ventaLeche, array $errores): void
    {
        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson('api/venta_leche', $ventaLeche);

        $response->assertStatus(422)->assertInvalid($errores);
    }
}
