<?php

namespace Tests\Feature;

use App\Models\Ganado;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class GanadoTest extends TestCase
{
    use RefreshDatabase;

    private array $cabeza_ganado = [
        'nombre' => 'test',
        'numero' => 392,
        'origen' => 'local',
        'sexo' => 'H',
        'tipo_id' => 4,
        'fecha_nacimiento' => '2015-02-17',
        'peso_nacimiento' => '30KG',
        'peso_destete' => '130KG',
        'peso_2year' => '300KG',
        'peso_actual' => '600KG',
        'estado' => 'sano',
    ];

    private int $cantidad_ganado = 10;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();
    }

    private function generarGanado(): Collection
    {
        return Ganado::factory()
            ->count($this->cantidad_ganado)
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasEstado(1)
            ->for($this->user)
            ->create();
    }
    public static function ErrorInputProvider(): array
    {
        return [
            'caso de que exista el nombre o numero' => [
                [
                    'nombre' => 'test',
                    'numero' => 300,
                    'origen' => 'local',
                    'sexo' => 'H',
                    'tipo_id' => '4',
                    'fecha_nacimiento' => '2015-03-02',
                    'peso_nacimiento' => '30KG',
                    'peso_destete' => '30KG',
                    'peso_2year' => '30KG',
                    'peso_actual' => '30KG',
                    'estado' => 'sano',
                ], ['nombre', 'numero']
            ],
            'caso de insertar datos erróneos' => [
                [
                    'nombre' => 'te',
                    'numero' => 'hj',
                    'origen' => 'ce',
                    'sexo' => 'm',
                    'tipo_id' => '30d',
                    'fecha_nacimiento' => '2015-13-02',
                    'peso_nacimiento' => '30KdG',
                    'peso_destete' => '30Kg',
                    'peso_2year' => 'd30KG',
                    'peso_actual' => '.30KG',
                    'estado' => 'sanito',
                ], [
                    'nombre', 'numero', 'origen', 'sexo', 'tipo_id', 'fecha_nacimiento',
                    'peso_nacimiento', 'peso_destete', 'peso_2year', 'peso_actual', 'estado'
                ]
            ],
            'caso de no insertar datos requeridos' => [
                [
                    'numero' => 300,
                    'origen' => 'local',
                    'fecha_nacimiento' => '2015-03-02',
                    'peso_nacimiento' => '30KG',
                    'peso_destete' => '30KG',
                    'peso_2year' => '30KG',
                    'peso_actual' => '30KG',
                    'estado' => 'sano',
                ], ['nombre', 'sexo', 'tipo_id']
            ],
        ];
    }



    /**
     * A basic feature test example.
     */

    public function test_obtener_cabezas_ganado(): void
    {
        $this->generarGanado();

        $response = $this->actingAs($this->user)->getJson('api/ganado');
        $response->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->has('cabezas_ganado', $this->cantidad_ganado));
    }


    public function test_creacion_cabeza_ganado(): void
    {

        $response = $this->actingAs($this->user)->postJson('api/ganado', $this->cabeza_ganado);

        $response->assertStatus(201)->assertJson(['ganado' => true]);
    }


    public function test_obtener_cabeza_ganado(): void
    {
        $cabezasGanado = $this->generarGanado();
        $idRandom = rand(0, $this->cantidad_ganado - 1);
        $idGanado = $cabezasGanado[$idRandom]->id;

        $response = $this->actingAs($this->user)->getJson(sprintf('api/ganado/%s', $idGanado), $this->cabeza_ganado);

        $response->assertStatus(200)->assertJson(['ganado' => true]);
    }
    
    public function test_actualizar_cabeza_ganado(): void
    {
        $cabezasGanado = $this->generarGanado();
        $idRandom = rand(0, $this->cantidad_ganado - 1);
        $idGanadoEditar = $cabezasGanado[$idRandom]->id;

        $response = $this->actingAs($this->user)->putJson(sprintf('api/ganado/%s', $idGanadoEditar), $this->cabeza_ganado);

        $response->assertStatus(200)->assertJson(['ganado' => true]);
    }

    public function test_actualizar_cabeza_ganado_con_otro_existente_repitiendo_campos_unicos(): void
    {
       Ganado::factory()->hasPeso(1)
            ->hasEvento(1)
            ->hasEstado(1)->for($this->user)->create(['nombre' => 'test','numero'=>392]);

        $cabezasGanado = $this->generarGanado();
        $idRandom = rand(0, $this->cantidad_ganado - 1);
        $idGanadoEditar = $cabezasGanado[$idRandom]->id;

        $response = $this->actingAs($this->user)->putJson(sprintf('api/ganado/%s', $idGanadoEditar), $this->cabeza_ganado);

        $response->assertStatus(422)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['errors.nombre','errors.numero'])
        ->etc());
    }
    
    public function test_actualizar_cabeza_ganado_sin_modificar_campos_unicos(): void
    {
        $ganado=Ganado::factory()->hasPeso(1)
            ->hasEvento(1)
            ->hasEstado(1)->for($this->user)->create(['nombre' => 'test','numero'=>392]);

        $response = $this->actingAs($this->user)->putJson(sprintf('api/ganado/%s', $ganado->id), $this->cabeza_ganado);

        $response->assertStatus(200)->assertJson(['ganado' => true]);
    }

    public function test_eliminar_cabeza_ganado(): void
    {
        $cabezasGanado = $this->generarGanado();
        $idRandom = rand(0, $this->cantidad_ganado - 1);
        $idToDelete = $cabezasGanado[$idRandom]->id;


        $response = $this->actingAs($this->user)->deleteJson(sprintf('api/ganado/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['ganadoID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_cabeza_ganado($ganado, $errores): void
    {
        Ganado::factory()->for($this->user)->create(['nombre' => 'test', 'numero' => 300]);

        $response = $this->actingAs($this->user)->postJson('api/ganado', $ganado);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_autorizacion_maniupular__cabeza_ganado_otro_usuario(): void
    {
        $otroUsuario = User::factory()->create();

        $ganadoOtroUsuario = Ganado::factory()
            ->hasPeso(1)->hasEvento(1)
            ->hasEstado(1)
            ->for($otroUsuario)
            ->create();
       
        $idGanadoOtroUsuario = $ganadoOtroUsuario->id;

         $this->generarGanado();

        $response = $this->actingAs($this->user)->putJson(sprintf('api/ganado/%s', $idGanadoOtroUsuario), $this->cabeza_ganado);

        $response->assertStatus(403);
    }
}
