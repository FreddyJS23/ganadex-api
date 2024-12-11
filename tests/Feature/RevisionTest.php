<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Finca;
use App\Models\Ganado;
use App\Models\Personal;
use App\Models\Revision;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class RevisionTest extends TestCase
{
    use RefreshDatabase;

    private array $revision = [
        'diagnostico' => 'revisar',
        'tratamiento' => 'medicina',
        'fecha' => '2020-10-02',

    ];

    private int $cantidad_revision = 10;

    private $user;
    private $ganado;
    private $estado;
    private $veterinario;
    private $url;
    private $finca;

    protected function setUp(): void
    {
        parent::setUp();

        $this->estado = Estado::all();

        $this->user
            = User::factory()->create();

        $this->user->assignRole('admin');

            $this->finca
            = Finca::factory()
            ->hasAttached($this->user)
            ->create();

        $this->veterinario
        = Personal::factory()
            ->for($this->finca)
            ->create(['cargo_id'=>2]);

            $this->ganado
            = Ganado::factory()
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->finca)
            ->create();

        $this->url = sprintf('api/ganado/%s/revision', $this->ganado->id);
    }

    private function generarRevision(): Collection
    {
        return Revision::factory()
            ->count($this->cantidad_revision)
            ->for($this->ganado)
            ->create(['personal_id'=>$this->veterinario]);
    }
    public static function ErrorInputProvider(): array
    {
        return [

            'caso de insertar datos erróneos' => [
                [
                    'diagnostico' => 'te',
                    'tratamiento' => 'hj',
                    'personal_id'=> 'd'
                ], ['diagnostico', 'tratamiento','personal_id']
            ],
            'caso de no insertar datos requeridos' => [
                [], ['diagnostico', 'tratamiento','personal_id']
            ],
            'caso de insertar un personal que no sea veterinario' => [
                [
                    'personal_id' => 2
                ], ['personal_id']
            ],
        ];
    }



    /**
     * A basic feature test example.
     */

    public function test_obtener_revisiones(): void
    {
        $this->generarRevision();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->getJson($this->url);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'revisiones',
                    $this->cantidad_revision,
                    fn (AssertableJson $json) =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'diagnostico' => 'string',
                        'tratamiento' => 'string',
                    ])->has(
                        'veterinario',
                        fn(AssertableJson $json)
                        =>$json->whereAllType([
                            'id'=>'integer',
                            'nombre'=>'string']))
                )
            );
    }


    public function test_creacion_revision(): void
    {

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->postJson($this->url, $this->revision + ['personal_id'=>$this->veterinario->id]);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'revision',
                    fn (AssertableJson $json) =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'diagnostico' => 'string',
                        'tratamiento' => 'string',
                    ])->has(
                    'veterinario',
                    fn (AssertableJson $json)
                    => $json->whereAllType([
                        'id' => 'integer',
                        'nombre' => 'string'
                    ])
                )
                )
            );
    }


    public function test_obtener_revision(): void
    {
        $revisiones = $this->generarRevision();

        $idRandom = rand(0, $this->cantidad_revision - 1);
        $idRevision = $revisiones[$idRandom]->id;
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->getJson(sprintf($this->url . '/%s', $idRevision));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'revision',
                    fn (AssertableJson $json) =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'fecha' => 'string',
                        'diagnostico' => 'string',
                        'tratamiento' => 'string',
                    ])->has(
                    'veterinario',
                    fn (AssertableJson $json)
                    => $json->whereAllType([
                        'id' => 'integer',
                        'nombre' => 'string'
                    ])
                )
                )
            );
    }
    public function test_actualizar_revision(): void
    {
        $revisiones = $this->generarRevision();
        $idRandom = rand(0, $this->cantidad_revision - 1);
        $idRevisionEditar = $revisiones[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->putJson(sprintf($this->url . '/%s', $idRevisionEditar), $this->revision);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'revision',
                    fn (AssertableJson $json) =>
                    $json->where('diagnostico',$this->revision['diagnostico'])
                    ->where('tratamiento',$this->revision['tratamiento'])
                ->has(
                    'veterinario',
                    fn (AssertableJson $json)
                    => $json->whereAllType([
                        'id' => 'integer',
                        'nombre' => 'string'
                    ])
                )
                    ->etc()
                )
            );
    }

    public function test_eliminar_revision(): void
    {
        $revisiones = $this->generarRevision();
        $idRandom = rand(0, $this->cantidad_revision - 1);
        $idToDelete = $revisiones[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->deleteJson(sprintf($this->url . '/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['revisionID' => $idToDelete]);
    }
    public function test_obtener_revisiones_de_todas_las_vacas(): void
    {
        Ganado::factory()
            ->count(10)
            ->hasPeso(1)
            ->hasRevision(5,['personal_id'=>$this->veterinario->id])
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->finca)
            ->create();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->getJson(route('todasRevisiones'));
        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has('todas_revisiones.1', fn (AssertableJson $json) => $json->whereAllType([
                    'id' => 'integer',
                    'numero' => 'integer',
                    'diagnostico' => 'string',
                    'ultima_revision' => 'string',
                    'proxima_revision' => 'string|null',
                    'total_revisiones' => 'integer'
                ]))
            );
    }


    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_revision($revision, $errores): void
    {
        //crear personal no veterinario
            Personal::factory()
            ->for($this->finca)
            ->create([
                'id'=>2,
                'ci' => 28472738,
                'nombre' => 'juan',
                'apellido' => 'perez',
                'fecha_nacimiento' => '2000-02-12',
                'telefono' => '0424-1234567',
                'cargo_id' => 1,
            ]);;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->postJson($this->url, $revision);

        $response->assertStatus(422)->assertInvalid($errores);
    }
}
