<?php

namespace Tests\Feature;

use App\Models\Ganado;
use App\Models\GanadoDescarte;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;
use Illuminate\Support\Str;

class GanadoDescarteTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    private array $ganadoDescarte = [
        'nombre' => 'test',
        'numero' => 392,
        'origen' => 'local',
        'sexo' => 'M',
        'fecha_nacimiento' => '2015-02-17',

    ];

    private int $cantidad_ganadoDescarte = 10;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();
    }

    private function generarGanadoDescartes(): Collection
    {
        return GanadoDescarte::factory()
            ->count(10)
            ->for($this->user)
            ->forGanado(['user_id' => $this->user->id, 'sexo' => 'M', 'tipo_id' => 4])
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
                    'sexo' => 'M',
                    'tipo_id' => '4',
                    'fecha_nacimiento' => '2015-03-02',
                ], ['nombre', 'numero']
            ],
            'caso de insertar datos erróneos' => [
                [
                    'nombre' => 'te',
                    'numero' => 'hj',
                    'origen' => 'ce',
                    'fecha_nacimiento' => '2015-13-02',
                ], [
                    'nombre', 'numero', 'origen', 'fecha_nacimiento',
                ]
            ],
            'caso de no insertar datos requeridos' => [
                ['origen' => 'local'], ['nombre']
            ],
        ];
    }



    /**
     * A basic feature test example.
     */

    public function test_obtener_ganadoDescartes(): void
    {
        $this->generarGanadoDescartes();

        $response = $this->actingAs($this->user)->getJson('api/ganadoDescarte');

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'ganado_descartes',
                    $this->cantidad_ganadoDescarte,
                    fn (AssertableJson $json) => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string',
                            'numero' => 'integer',
                            'origen' => 'string',
                            'fecha_nacimiento' => 'string',
                            'ganado_id'=> 'integer',
                            'estados' => 'array',
                            'pesos' => 'array|null',
                        ])
                        ->where('sexo', 'M')
                        ->where('tipo', fn (string $tipoGanado) => Str::contains($tipoGanado, ['becerro', 'maute','novillo','adulto']))
                )
            );
    }


    public function test_creacion_ganadoDescarte(): void
    {

        $response = $this->actingAs($this->user)->postJson('api/ganadoDescarte', $this->ganadoDescarte);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'ganado_descarte',
                    fn (AssertableJson $json) => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string',
                            'numero' => 'integer',
                            'origen' => 'string',
                            'tipo' => 'string',
                            'fecha_nacimiento' => 'string',
                            'ganado_id'=> 'integer',
                            'estados' => 'array',
                            'pesos' => 'array|null',
                        ])
                        ->where('sexo', 'M')
                    ->where('tipo', fn (string $tipoGanado) => Str::contains($tipoGanado, ['becerro', 'maute', 'novillo', 'adulto']))
                )
            );
    }


    public function test_obtener_ganadoDescarte(): void
    {
        $ress = $this->generarGanadoDescartes();
        $idRandom = rand(0, $this->cantidad_ganadoDescarte - 1);
        $idRes = $ress[$idRandom]->id;


        $response = $this->actingAs($this->user)->getJson(sprintf('api/ganadoDescarte/%s', $idRes));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'ganado_descarte',
                    fn (AssertableJson $json) => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string',
                            'numero' => 'integer',
                            'origen' => 'string',
                            'fecha_nacimiento' => 'string',
                            'ganado_id'=> 'integer',
                            'estados' => 'array',
                            'pesos' => 'array|null',
                        ])
                        ->where('sexo', 'M')
                    ->where('tipo', fn (string $tipoGanado) => Str::contains($tipoGanado, ['becerro', 'maute', 'novillo', 'adulto']))
                        
                )
            );
    }

    public function test_actualizar_ganadoDescarte(): void
    {
        $ress = $this->generarGanadoDescartes();
        $idRandom = rand(0, $this->cantidad_ganadoDescarte - 1);
        $idResEditar = $ress[$idRandom]->id;

        $response = $this->actingAs($this->user)->putJson(sprintf('api/ganadoDescarte/%s', $idResEditar), $this->ganadoDescarte);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->where('ganado_descarte.nombre', $this->ganadoDescarte['nombre'])
                ->where('ganado_descarte.numero', $this->ganadoDescarte['numero'])
                ->where('ganado_descarte.origen', $this->ganadoDescarte['origen'])
                ->where('ganado_descarte.sexo', $this->ganadoDescarte['sexo'])
                ->where('ganado_descarte.fecha_nacimiento', $this->ganadoDescarte['fecha_nacimiento'])
                ->where('ganado_descarte.tipo', fn (string $tipoGanado) => Str::contains($tipoGanado, ['becerro', 'maute', 'novillo', 'adulto']))
                ->etc()
        );
    }

    public function test_actualizar_res_con_otro_existente_repitiendo_campos_unicos(): void
    {
        GanadoDescarte::factory()
            ->for($this->user)
            ->for(Ganado::factory()->for($this->user)->create(['nombre' => 'test', 'numero' => 392]))
            ->create();

        $ganadoDescarte = $this->generarGanadoDescartes();
        $idRandom = rand(0, $this->cantidad_ganadoDescarte - 1);
        $idResEditar = $ganadoDescarte[$idRandom]->id;

        $response = $this->actingAs($this->user)->putJson(sprintf('api/ganadoDescarte/%s', $idResEditar), $this->ganadoDescarte);

        $response->assertStatus(422)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['errors.nombre', 'errors.numero'])
        ->etc());
    }

    public function test_actualizar_res_sin_modificar_campos_unicos(): void
    {
        $ganadoDescarte = GanadoDescarte::factory()
            ->for($this->user)
            ->for(Ganado::factory()->for($this->user)->create(['nombre' => 'test', 'numero' => 392]))
            ->create();

        $response = $this->actingAs($this->user)->putJson(sprintf('api/ganadoDescarte/%s', $ganadoDescarte->id), $this->ganadoDescarte);

        $response->assertStatus(200)->assertJson(['ganado_descarte' => true]);
    }


    public function test_eliminar_res(): void
    {
        $ress = $this->generarGanadoDescartes();
        $idRandom = rand(0, $this->cantidad_ganadoDescarte - 1);
        $idToDelete = $ress[$idRandom]->id;


        $response = $this->actingAs($this->user)->deleteJson(sprintf('api/ganadoDescarte/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['ganado_descarteID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_res($ganadoDescarte, $errores): void
    {
        GanadoDescarte::factory()
            ->for($this->user)
            ->for(Ganado::factory()->for($this->user)->create(['nombre' => 'test', 'numero' => 300]))
            ->create();

        $response = $this->actingAs($this->user)->postJson('api/ganadoDescarte', $ganadoDescarte);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_autorizacion_maniupular__res_otro_usuario(): void
    {
        $otroUsuario = User::factory()->create();

        $resOtroUsuario = GanadoDescarte::factory()
            ->for($otroUsuario)
            ->for(Ganado::factory()->for($otroUsuario))
            ->create();

        $idResOtroUsuario = $resOtroUsuario->id;

        $this->generarGanadoDescartes();

        $response = $this->actingAs($this->user)->putJson(sprintf('api/ganadoDescarte/%s', $idResOtroUsuario), $this->ganadoDescarte);

        $response->assertStatus(403);
    }
}
