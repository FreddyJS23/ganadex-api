<?php

namespace Tests\Feature;

use App\Models\Finca;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class FincaTest extends TestCase
{
    use RefreshDatabase;

    private array $finca = [
        'nombre' => 'finca test',
    ];

    private $fincaEnSesion;

    private int $cantidad_fincas = 10;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->hasConfiguracion()->create();

            $this->user->assignRole('admin');

            $this->fincaEnSesion
            = Finca::factory()
            ->for($this->user)
            ->create(['nombre'=>'finca_sesion']);
    }

    private function generarFincas(): Collection
    {
        return Finca::factory()
            ->count($this->cantidad_fincas)
            ->for($this->user)
            ->create();
    }
    public static function ErrorInputProvider(): array
    {
        return [
            'caso de que exista la finca' => [
                [
                    'nombre' => 'test',
                ],
                ['nombre']
            ],
            'caso de insertar datos erróneos' => [
                [
                    'nombre' => 'te',
                ],
                ['nombre']
            ],
            'caso de no insertar datos requeridos' => [
                [],
                ['nombre']
            ],
        ];
    }



    public function test_obtener_fincas_usuario(): void
    {
       $this->generarFincas();

        $response = $this->actingAs($this->user)->getJson(route('finca.index'));

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json) =>
            $json->has(
                'fincas',
                $this->cantidad_fincas + 1,
                fn(AssertableJson $json)
                => $json->whereAllType([
                    'id' => 'integer',
                    'nombre' => 'string',
                    'fecha_creacion'=>'string'
                ])
            )
        );
    }


    public function test_creacion_finca(): void
    {

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->fincaEnSesion->id])->postJson(route('finca.store'), $this->finca);

        $response->assertStatus(201)->assertJson(
            fn(AssertableJson $json) =>
            $json->has(
                'finca',
                fn(AssertableJson $json)
                => $json->whereAllType([
                    'id' => 'integer',
                    'nombre' => 'string',
                    'fecha_creacion'=>'string'
                ])
            )
        );
    }

     public function test_actualizar_finca(): void
    {
        $finca = $this->generarFincas();
        $idRandom = rand(0, $this->cantidad_fincas - 1);
        $idFincaEditar = $finca[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $idFincaEditar])->putJson(route('finca.update', ['finca' => $idFincaEditar]), $this->finca);

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json) =>
            $json->has(
                'finca',
                fn(AssertableJson $json) =>
                $json->where('id', $idFincaEditar)
                ->where('nombre', $this->finca['nombre'])
                ->etc()

            )
        );
    }

     public function test_actualizar_finca_con_otro_existente_repitiendo_campos_unicos(): void
    {
        $fincaExistente = finca::factory()->for($this->user)->create();

        $finca = $this->generarFincas();
        $idRandom = rand(0, $this->cantidad_fincas - 1);
        $idfincaEditar = $finca[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $idfincaEditar])->putJson(route('finca.update', ['finca' => $finca[$idRandom]]), ['nombre' => 'finca_sesion']);

        $response->assertStatus(422)->assertJson(fn(AssertableJson $json) =>
        $json->hasAll(['errors.nombre'])->etc()
            );
    }

    public function test_obtener_finca_en_sesion(): void
    {
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->fincaEnSesion])->getJson(route('verificar_sesion_finca'));

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json) =>
            $json->has(
                'finca',
                fn(AssertableJson $json)
                => $json->whereAllType([
                    'id' => 'integer',
                    'nombre' => 'string',
                    'fecha_creacion'=>'string'
                ])
            )
        );
    }

    public function test_creacion_sesion_finca(): void
    {

        $response = $this->actingAs($this->user)->getJson(route('crear_sesion_finca',['finca'=>$this->fincaEnSesion]));

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json) =>
            $json->has(
                'finca',
                fn(AssertableJson $json)
                => $json->whereAllType([
                    'id' => 'integer',
                    'nombre' => 'string',
                    'fecha_creacion'=>'string'
                ])
            )
        );
    }

    public function test_error_creacion_sesion_finca_otro_usuario(): void
    {
        $otroUsuario = User::factory()->create();

        $fincaOtroUsuario = finca::factory()->for($otroUsuario)->create();

        $idfincaOtroUsuario = $fincaOtroUsuario->id;

        $response = $this->actingAs($this->user)->getJson(route('crear_sesion_finca',['finca'=>$idfincaOtroUsuario]));

        $response->assertStatus(403);
    }

    public function test_error_obtener_finca_en_sesion_no_siendo_administrador(): void
    {
        $this->user->syncRoles('veterinario');
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->fincaEnSesion])->getJson(route('verificar_sesion_finca'));

        $response->assertStatus(403);
    }


    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_finca($finca, $errores): void
    {
        $fincaTest=Finca::factory()->for($this->user)->create(['nombre'=>'test']);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $fincaTest->id])->postJson(route('finca.store'), $finca);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_autorizacion_maniupular__finca_otro_usuario(): void
    {
        $otroUsuario = User::factory()->create();

        $fincaOtroUsuario = finca::factory()->for($otroUsuario)->create();

        $idfincaOtroUsuario = $fincaOtroUsuario->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->fincaEnSesion])->putJson(route('finca.update', ['finca' => $idfincaOtroUsuario]), $this->finca);

        $response->assertStatus(403);
    }
}
