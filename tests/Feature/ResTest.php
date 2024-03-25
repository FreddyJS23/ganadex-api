<?php

namespace Tests\Feature;

use App\Models\Ganado;
use App\Models\Res;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;
use Illuminate\Support\Str;

class ResTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    private array $res = [
        'nombre' => 'test',
        'numero' => 392,
        'origen' => 'local',
        'sexo' => 'M',
        'fecha_nacimiento' => '2015-02-17',

    ];

    private int $cantidad_res = 10;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();
    }

    private function generarReses(): Collection
    {
        return Res::factory()
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

    public function test_obtener_reses(): void
    {
        $this->generarReses();

        $response = $this->actingAs($this->user)->getJson('api/res');

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'reses',
                    $this->cantidad_res,
                    fn (AssertableJson $json) => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string',
                            'numero' => 'integer',
                            'origen' => 'string',
                            'fecha_nacimiento' => 'string',

                        ])
                        ->where('sexo', 'M')
                        ->where('tipo', fn (string $tipoGanado) => Str::contains($tipoGanado, ['becerro', 'maute','novillo','adulto']))
                )
            );
    }


    public function test_creacion_res(): void
    {

        $response = $this->actingAs($this->user)->postJson('api/res', $this->res);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'res',
                    fn (AssertableJson $json) => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string',
                            'numero' => 'integer',
                            'origen' => 'string',
                            'tipo' => 'string',
                            'fecha_nacimiento' => 'string',
                        ])
                        ->where('sexo', 'M')
                    ->where('tipo', fn (string $tipoGanado) => Str::contains($tipoGanado, ['becerro', 'maute', 'novillo', 'adulto']))
                )
            );
    }


    public function test_obtener_res(): void
    {
        $ress = $this->generarReses();
        $idRandom = rand(0, $this->cantidad_res - 1);
        $idRes = $ress[$idRandom]->id;


        $response = $this->actingAs($this->user)->getJson(sprintf('api/res/%s', $idRes));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'res',
                    fn (AssertableJson $json) => $json
                        ->whereAllType([
                            'id' => 'integer',
                            'nombre' => 'string',
                            'numero' => 'integer',
                            'origen' => 'string',
                            'fecha_nacimiento' => 'string',
                        ])
                        ->where('sexo', 'M')
                    ->where('tipo', fn (string $tipoGanado) => Str::contains($tipoGanado, ['becerro', 'maute', 'novillo', 'adulto']))
                        
                )
            );
    }

    public function test_actualizar_res(): void
    {
        $ress = $this->generarReses();
        $idRandom = rand(0, $this->cantidad_res - 1);
        $idResEditar = $ress[$idRandom]->id;

        $response = $this->actingAs($this->user)->putJson(sprintf('api/res/%s', $idResEditar), $this->res);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->where('res.nombre', $this->res['nombre'])
                ->where('res.numero', $this->res['numero'])
                ->where('res.origen', $this->res['origen'])
                ->where('res.sexo', $this->res['sexo'])
                ->where('res.fecha_nacimiento', $this->res['fecha_nacimiento'])
                ->where('res.tipo', fn (string $tipoGanado) => Str::contains($tipoGanado, ['becerro', 'maute', 'novillo', 'adulto']))
                ->etc()
        );
    }

    public function test_actualizar_res_con_otro_existente_repitiendo_campos_unicos(): void
    {
        Res::factory()
            ->for($this->user)
            ->for(Ganado::factory()->for($this->user)->create(['nombre' => 'test', 'numero' => 392]))
            ->create();

        $res = $this->generarReses();
        $idRandom = rand(0, $this->cantidad_res - 1);
        $idResEditar = $res[$idRandom]->id;

        $response = $this->actingAs($this->user)->putJson(sprintf('api/res/%s', $idResEditar), $this->res);

        $response->assertStatus(422)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['errors.nombre', 'errors.numero'])
        ->etc());
    }

    public function test_actualizar_res_sin_modificar_campos_unicos(): void
    {
        $res = Res::factory()
            ->for($this->user)
            ->for(Ganado::factory()->for($this->user)->create(['nombre' => 'test', 'numero' => 392]))
            ->create();

        $response = $this->actingAs($this->user)->putJson(sprintf('api/res/%s', $res->id), $this->res);

        $response->assertStatus(200)->assertJson(['res' => true]);
    }


    public function test_eliminar_res(): void
    {
        $ress = $this->generarReses();
        $idRandom = rand(0, $this->cantidad_res - 1);
        $idToDelete = $ress[$idRandom]->id;


        $response = $this->actingAs($this->user)->deleteJson(sprintf('api/res/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['resID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_res($res, $errores): void
    {
        Res::factory()
            ->for($this->user)
            ->for(Ganado::factory()->for($this->user)->create(['nombre' => 'test', 'numero' => 300]))
            ->create();

        $response = $this->actingAs($this->user)->postJson('api/res', $res);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_autorizacion_maniupular__res_otro_usuario(): void
    {
        $otroUsuario = User::factory()->create();

        $resOtroUsuario = Res::factory()
            ->for($otroUsuario)
            ->for(Ganado::factory()->for($otroUsuario))
            ->create();

        $idResOtroUsuario = $resOtroUsuario->id;

        $this->generarReses();

        $response = $this->actingAs($this->user)->putJson(sprintf('api/res/%s', $idResOtroUsuario), $this->res);

        $response->assertStatus(403);
    }
}
