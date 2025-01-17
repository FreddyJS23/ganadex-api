<?php

namespace Tests\Feature;

use App\Models\Comprador;
use App\Models\Finca;
use App\Models\Ganado;
use App\Models\Toro;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class ToroTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    private array $toro = [
        'nombre' => 'test',
        'numero' => 392,
        'origen' => 'local',
        'sexo' => 'M',
        'tipo_id' => 4,
        'fecha_nacimiento' => '2015-02-17',

    ];

    private int $cantidad_toro = 10;

    private $user;
    private $finca;
    private $toro_fallecido;
    private $toro_vendido;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();

            $this->user->assignRole('admin');

            $this->finca
            = Finca::factory()
            ->hasAttached($this->user)
            ->create();

            $comprador = Comprador::factory()->for($this->finca)->create()->id;
            $this->toro_fallecido= array_merge($this->toro,['estado_id'=>[2,3,4],'fecha_fallecimiento'=>'2020-10-02','causa'=>'enferma']);
            $this->toro_vendido =array_merge($this->toro,['estado_id'=>[5,6,7],'fecha_venta'=>'2020-10-02','precio'=>100,'comprador_id'=>$comprador]);
            $this->toro=array_merge($this->toro,['estado_id'=>[1]]);
    }

    private function generarToros(): Collection
    {
        return Toro::factory()
            ->count(10)
            ->for($this->finca)
            ->forGanado(['finca_id' => $this->finca->id, 'sexo' => 'M', 'tipo_id' => 4])
            ->create();
    }

    private function cambiarRol(User $user): void
    {
        $user->syncRoles('veterinario');
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
                ['origen' => 'local'], ['nombre', 'numero',]
            ],
        ];
    }



    /**
     * A basic feature test example.
     */

    public function test_obtener_toros(): void
    {
        $this->generarToros();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->getJson('api/toro');

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'toros',
                    $this->cantidad_toro,
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
                            'efectividad'=>'double|null',
                            'padre_en_partos'=>'integer',
                            'servicios'=>'integer|null',

                        ])
                    ->where('sexo','M')
                    ->where('tipo', 'adulto')
                )
            );
    }


    public function test_creacion_toro(): void
    {

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->postJson('api/toro', $this->toro);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'toro',
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
                            'efectividad'=>'double|null',
                            'padre_en_partos'=>'integer|null',
                            'servicios'=>'integer|null',
                        ])
                        ->where('sexo', 'M')
                        ->where('tipo', 'adulto')
                )
            );
    }

    public function test_creacion_toro_fallecida(): void
    {
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->postJson('api/toro', $this->toro_fallecido);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has(
                        'toro',
                        fn (AssertableJson $json) =>
                        $json
                            ->where('estados.0.estado','fallecido')
                            ->etc()
                )
            );
    }

    public function test_creacion_toro_vendido(): void
    {
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->postJson('api/toro', $this->toro_vendido);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->has(
                        'toro',
                        fn (AssertableJson $json) =>
                        $json
                            ->where('estados.0.estado','vendido')
                            ->etc()
                )
            );
    }


    public function test_obtener_toro(): void
    {
        $toros = $this->generarToros();
        $idRandom = rand(0, $this->cantidad_toro - 1);
        $idToro = $toros[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->getJson(sprintf('api/toro/%s', $idToro));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'toro',
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
                            'efectividad'=>'double|null',
                            'padre_en_partos'=>'integer',
                            'servicios'=>'integer|null',
                        ])
                        ->where('sexo', 'M')
                        ->where('tipo', 'adulto')
                )
            );
    }

    public function test_actualizar_toro(): void
    {
        $toros = $this->generarToros();
        $idRandom = rand(0, $this->cantidad_toro - 1);
        $idToroEditar = $toros[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->putJson(sprintf('api/toro/%s', $idToroEditar), $this->toro);

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json
                ->where('toro.nombre', $this->toro['nombre'])
                ->where('toro.numero', $this->toro['numero'])
                ->where('toro.origen', $this->toro['origen'])
                ->where('toro.sexo', $this->toro['sexo'])
                ->where('toro.fecha_nacimiento', $this->toro['fecha_nacimiento'])
                ->etc()
        );
    }

    public function test_actualizar_toro_con_otro_existente_repitiendo_campos_unicos(): void
    {
        Toro::factory()
            ->for($this->finca)
            ->for(Ganado::factory()->for($this->finca)->create(['nombre' => 'test', 'numero' => 392]))
            ->create();

        $toros = $this->generarToros();
        $idRandom = rand(0, $this->cantidad_toro - 1);
        $idToroEditar = $toros[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->putJson(sprintf('api/toro/%s', $idToroEditar), $this->toro);

        $response->assertStatus(422)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['errors.nombre', 'errors.numero'])
        ->etc());
    }

    public function test_actualizar_toro_sin_modificar_campos_unicos(): void
    {
        $toro =Toro::factory()
            ->for($this->finca)
            ->for(Ganado::factory()->for($this->finca)->create(['nombre' => 'test', 'numero' => 392]))
            ->create();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->putJson(sprintf('api/toro/%s', $toro->id), $this->toro);

        $response->assertStatus(200)->assertJson(['toro' => true]);
    }


    public function test_eliminar_toro(): void
    {
        $toros = $this->generarToros();
        $idRandom = rand(0, $this->cantidad_toro - 1);
        $idToDelete = $toros[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->deleteJson(sprintf('api/toro/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['toroID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_toro($toro, $errores): void
    {
        Toro::factory()
            ->for($this->finca)
            ->for(Ganado::factory()->for($this->finca)->create(['nombre' => 'test', 'numero' => 300]))
            ->create();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->postJson('api/toro', $toro);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_autorizacion_maniupular__toro_otro_finca(): void
    {
        $otroFinca = Finca::factory()
        ->hasAttached($this->user)
        ->create(['nombre' => 'otro_finca']);

        $toroOtroFinca = Toro::factory()
            ->for($otroFinca)
            ->for(Ganado::factory()->for($otroFinca))
            ->create();

        $idToroOtroFinca = $toroOtroFinca->id;

        $this->generarToros();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->putJson(sprintf('api/toro/%s', $idToroOtroFinca), $this->toro);

        $response->assertStatus(403);
    }

    public function test_veterinario_no_autorizado_a_crear_toro(): void
    {
        $this->cambiarRol($this->user);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->postJson(route('toro.store'), $this->toro);

        $response->assertStatus(403);
    }

    public function test_veterinario_no_autorizado_a_actualizar_toro(): void
    {
        $this->cambiarRol($this->user);

        $toro = $this->generarToros();
        $idRandom = rand(0, $this->cantidad_toro - 1);
        $idToroEditar = $toro[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->putJson(route('toro.update',['toro'=>$idToroEditar]), $this->toro);

        $response->assertStatus(403);
    }


    public function test_veterinario_no_autorizado_a_eliminar_toro(): void
    {
        $this->cambiarRol($this->user);

        $toro = $this->generarToros();
        $idRandom = rand(0, $this->cantidad_toro - 1);
        $idToroEliminar = $toro[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->deleteJson(route('toro.destroy',['toro'=>$idToroEliminar]));

        $response->assertStatus(403);
    }
}
