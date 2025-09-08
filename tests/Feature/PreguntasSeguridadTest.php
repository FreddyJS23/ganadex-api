<?php

namespace Tests\Feature;

use App\Models\PreguntasSeguridad;
use App\Models\RespuestasSeguridad;
use Tests\Feature\Common\NeedsSetupRequest;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class PreguntasSeguridadTest extends TestCase
{
    use NeedsSetupRequest {
        NeedsSetupRequest::setUp as needsSetupRequestSetUp;
    }

    private $cantidad_preguntas_seguridad;


    protected function setUp(): void
    {
        $this->needsSetupRequestSetUp();
        $this->cantidad_preguntas_seguridad = PreguntasSeguridad::count();
    }

    public function test_obtener_preguntas_seguridad_del_sistema(): void
    {

        $response = $this->actingAs($this->user)->getJson(route('preguntas_seguridad.index'));

        $response->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'preguntas_seguridad',
                    $this->cantidad_preguntas_seguridad,
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'pregunta' => 'string',
                    ])
                )
            );
    }

    /* verificar para no devolver las mismas preguntas de seguridad que el usuario ya tiene */
    public function test_obtener_preguntas_seguridad_del_sistema_y_el_usuario_ya_tienes_preguntas_seguridad(): void
    {

        RespuestasSeguridad::factory()
            ->count(3)
            ->for($this->user)
            ->sequence(
                ['preguntas_seguridad_id' => 1],
                ['preguntas_seguridad_id' => 2],
                ['preguntas_seguridad_id' => 3],
            )
            ->create();


        $response = $this->actingAs($this->user)->getJson(route('preguntas_seguridad.index'));

        $response->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'preguntas_seguridad',
                    $this->cantidad_preguntas_seguridad - 3,
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'pregunta' => 'string',
                    ])
                )
            );
    }
}
