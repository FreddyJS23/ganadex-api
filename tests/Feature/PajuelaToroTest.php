<?php

namespace Tests\Feature;

use App\Models\Finca;
use App\Models\PajuelaToro;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class PajuelaToroTest extends TestCase
{
    use RefreshDatabase;

    private array $pajuela_toro = [
        'codigo' => '21DDSQ7',
        'descripcion' => 'Toro de prueba',
        'fecha' => '2023-09-12',
    ];

    private int $cantidad_pajuelaToro = 10;

    private $user;
    private $finca;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->hasConfiguracion()->create();

        $this->user->assignRole('admin');

            $this->finca
            = Finca::factory()
            ->for($this->user)
            ->create();
    }

    private function generarPajuelasToros(): Collection
    {
        return PajuelaToro::factory()
            ->count($this->cantidad_pajuelaToro)
            ->for($this->finca)
            ->create();
    }

    private function cambiarRol(User $user): void
    {
        $user->syncRoles('veterinario');
    }


    public static function ErrorInputProvider(): array
    {
        return [
            'caso de insertar datos erróneos' => [
                [
                    'codigo' => 33284,
                    'descripcion' => 231,
                    'fecha' => '09-12-2023',
                ], ['codigo', 'descripcion', 'fecha']
            ],
            'caso de no insertar datos requeridos' => [
                [],
                ['codigo','fecha']
            ],
        ];
    }


    /**
     * A basic feature test example.
     */

    public function test_obtener_todo_pajuelas_toro(): void
    {
        $this->generarPajuelasToros();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('pajuela_toros.index'));
        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'pajuela_toros',
                    $this->cantidad_pajuelaToro,
                    fn (AssertableJson $json) => $json->whereAllType([
                        'id' => 'integer',
                        'codigo' => 'string',
                        'descripcion' => 'string',
                        'fecha' => 'string',
                    ])
                )
            );
    }


    public function test_creacion_pajuela_toro(): void
    {

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(route('pajuela_toros.store'), $this->pajuela_toro);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'pajuela_toro',
                    fn (AssertableJson $json) => $json->whereAllType([
                        'id' => 'integer',
                        'codigo' => 'string',
                        'descripcion' => 'string',
                        'fecha' => 'string',
                    ])
                )
            );
    }


    public function test_obtener_pajuela_toro(): void
    {
        $pajuela_torols = $this->generarPajuelasToros();
        $idRandom = rand(0, $this->cantidad_pajuelaToro - 1);
        $idPajuelaToro = $pajuela_torols[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('pajuela_toros.show', ['pajuela_toro' => $idPajuelaToro]));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'pajuela_toro',
                    fn (AssertableJson $json) => $json->whereAllType([
                        'id' => 'integer',
                        'codigo' => 'string',
                        'descripcion' => 'string',
                        'fecha' => 'string',
                    ])
                )
            );
    }

    public function test_actualizar_pajuela_toro(): void
    {
        $pajuela_toro = $this->generarPajuelasToros();
        $idRandom = rand(0, $this->cantidad_pajuelaToro - 1);
        $idPajuelaToroEditar = $pajuela_toro[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(route('pajuela_toros.update', ['pajuela_toro' => $idPajuelaToroEditar]), $this->pajuela_toro);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json) => $json->has(
                    'pajuela_toro',
                    fn (AssertableJson $json) => $json
                        ->where('codigo', $this->pajuela_toro['codigo'])
                        ->where('descripcion', $this->pajuela_toro['descripcion'])
                        ->where('fecha', $this->pajuela_toro['fecha'])
                        ->etc()
                )
            );
    }


    public function test_eliminar_pajuela_toro(): void
    {
        $pajuela_toro = $this->generarPajuelasToros();
        $idRandom = rand(0, $this->cantidad_pajuelaToro - 1);
        $idToDelete = $pajuela_toro[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->deleteJson(route('pajuela_toros.destroy', ['pajuela_toro' => $idToDelete]));

        $response->assertStatus(200)->assertJson(['pajuela_toroID' => $idToDelete]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_pajuela_toro($pajuela_toro, $errores): void
    {
        PajuelaToro::factory()->for($this->finca)->create(['codigo' => 28472738]);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(route('pajuela_toros.store'), $pajuela_toro);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_autorizacion_maniupular__pajuela_otro_finca(): void
    {
        $otroFinca = Finca::factory()
        ->for($this->user)
        ->create(['nombre' => 'otro_finca']);

        $pajuela_torolOtroFinca = PajuelaToro::factory()->for($otroFinca)->create();

        $idPajuelaOtroFinca = $pajuela_torolOtroFinca->id;

        $this->generarPajuelasToros();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(route('pajuela_toros.update', ['pajuela_toro' => $idPajuelaOtroFinca]), $this->pajuela_toro);

        $response->assertStatus(403);
    }

    public function test_veterinario_no_autorizado_a_crear_pajuela_toro(): void
    {
        $this->cambiarRol($this->user);

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(route('pajuela_toros.store'), $this->pajuela_toro);

        $response->assertStatus(403);
    }

    public function test_veterinario_no_autorizado_a_actualizar_pajuela_toro(): void
    {
        $this->cambiarRol($this->user);

        $pajuelasToro = $this->generarPajuelasToros();
        $idRandom = rand(0, $this->cantidad_pajuelaToro - 1);
        $idPajuelaToroEditar = $pajuelasToro[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(route('pajuela_toros.update', ['pajuela_toro' => $idPajuelaToroEditar]), $this->pajuela_toro);

        $response->assertStatus(403);
    }


    public function test_veterinario_no_autorizado_a_eliminar_pajuela_toro(): void
    {
        $this->cambiarRol($this->user);

        $pajuelasToro = $this->generarPajuelasToros();
        $idRandom = rand(0, $this->cantidad_pajuelaToro - 1);
        $idPajuelaToroEliminar = $pajuelasToro[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->deleteJson(route('pajuela_toros.destroy', ['pajuela_toro' => $idPajuelaToroEliminar]));

        $response->assertStatus(403);
    }
}
