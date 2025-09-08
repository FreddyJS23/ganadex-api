<?php

namespace Tests\Feature;

use App\Models\Precio;
use Illuminate\Database\Eloquent\Collection;
use Tests\Feature\Common\NeedsSetupRequest;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class PrecioTest extends TestCase
{
    use NeedsSetupRequest {
        NeedsSetupRequest::setUp as needsSetupRequestSetUp;
    }

    private array $precio = [
        'precio' => 30,
        'fecha' => '2020-10-02',

    ];

    private int $cantidad_precio = 10;



    protected function setUp(): void
    {
        $this->needsSetupRequestSetUp();
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
                ],
                ['precio']
            ],
            'caso de no insertar datos requeridos' => [
                [],
                ['precio']
            ],
        ];
    }

    /**
     * A basic feature test example.
     */

    public function test_obtener_precios(): void
    {
        $this->generarPrecio();

        $response = $this->setUpRequest()->getJson('api/precio');

        $response->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'precios',
                    $this->cantidad_precio,
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
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

        $response = $this->setUpRequest()->postJson('api/precio', $this->precio);

        $response->assertStatus(201)->assertJson(
            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json->first(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
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
    public function test_error_validacion_registro_precio(array $precio, array $errores): void
    {
        $response = $this->setUpRequest()->postJson('api/precio', $precio);

        $response->assertStatus(422)->assertInvalid($errores);
    }
}
