<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Hacienda;
use App\Models\Ganado;
use App\Models\Leche;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class PesajeLecheTest extends TestCase
{
    use RefreshDatabase;

    private array $pesoLeche = [
        'peso_leche' => '30',
        'fecha' => '2020-10-02',


    ];

    private int $cantidad_pesoLeche = 10;

    private $user;
    private $ganado;
    private $ganadoFallecido;
    private $ganadoVendido;
    private $ganadoSano;
    private $estado;
    private $estadoLactancia;
    private $estadoSano;
    private $estadoVendido;
    private $estadoFallecido;
    private string $url;
    private $hacienda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->estadoSano = Estado::find(1);
        $this->estadoLactancia = Estado::find(4);
        $this->estadoFallecido = Estado::find(2);
        $this->estadoVendido = Estado::find(5);
        $this->estado = Estado::all();

        $this->user
            = User::factory()->hasConfiguracion()->create();

        $this->user->assignRole('admin');

            $this->hacienda
            = Hacienda::factory()
            ->for($this->user)
            ->create();

        $this->ganado
            = Ganado::factory()
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estadoLactancia)
            ->for($this->hacienda)
            ->create();

        $this->url = sprintf('api/ganado/%s/pesaje_leche', $this->ganado->id);
    }

    private function generarPesajesLeche(): Collection
    {
        return Leche::factory()
            ->count($this->cantidad_pesoLeche)
            ->for($this->ganado)
            ->for($this->hacienda)
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
                    'peso_leche' => 'dd33'
                ], ['peso_leche']
            ],
            'caso de no insertar datos requeridos' => [
                [], ['peso_leche']
            ],
        ];
    }



    /**
     * A basic feature test example.
     */

    public function test_obtener_todos_pesaje_de_leches(): void
    {

        $this->generarPesajesLeche();


        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson($this->url);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'pesajes_leche',
                    $this->cantidad_pesoLeche,
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType([
                        'id' => 'integer',
                        'pesaje' => 'integer',
                        'fecha' => 'string',
                    ])
                )
            );
    }


    public function test_creacion_pesaje_leche(): void
    {

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson($this->url, $this->pesoLeche);

        $response->assertStatus(201)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'pesaje_leche',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType([
                        'id' => 'integer',
                        'pesaje' => 'string',
                        'fecha' => 'string',
                    ])
                )
            );
    }


    public function test_obtener_pesaje_leche(): void
    {
        $pesajesDeLeche = $this->generarPesajesLeche();

        $idRandom = random_int(0, $this->cantidad_pesoLeche - 1);
        $idPesoLeche = $pesajesDeLeche[$idRandom]->id;
        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(sprintf($this->url . '/%s', $idPesoLeche));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'pesaje_leche',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType([
                        'id' => 'integer',
                        'pesaje' => 'integer',
                        'fecha' => 'string',
                    ])
                )
            );
    }
    public function test_actualizar_pesoLeche(): void
    {
        $pesajesDeLeche = $this->generarPesajesLeche();
        $idRandom = random_int(0, $this->cantidad_pesoLeche - 1);
        $idPesoLecheEditar = $pesajesDeLeche[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(sprintf($this->url . '/%s', $idPesoLecheEditar), $this->pesoLeche);

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'pesaje_leche',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json
                        ->where('pesaje', $this->pesoLeche['peso_leche'])
                        ->etc()
                )
            );
    }

    public function test_eliminar_pesoLeche(): void
    {
        $pesajesDeLeche = $this->generarPesajesLeche();
        $idRandom = random_int(0, $this->cantidad_pesoLeche - 1);
        $idToDelete = $pesajesDeLeche[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->deleteJson(sprintf($this->url . '/%s', $idToDelete));

        $response->assertStatus(200)->assertJson(['pesajeLecheID' => $idToDelete]);
    }

    public function test_obtener_pesajes_leche_de_todas_las_vacas(): void
    {
        Ganado::factory()
            ->count(10)
            ->hasPeso(1)
            ->has(Leche::factory()->for($this->hacienda)->count(3), 'pesajes_leche')
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->hacienda)
            ->create();

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->getJson(route('todosPesajesLeche'));

        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has('todos_pesaje_leche.1', fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->whereAllType([
                    'id' => 'integer',
                    'nombre' => 'string',
                    'numero' => 'integer',
                    'ultimo_pesaje' => 'string|null',
                    'pesaje_este_mes' => 'boolean',
                ]))
            );
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_pesoLeche(array $pesoLeche, array $errores): void
    {

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson($this->url, $pesoLeche);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_veterinario_no_autorizado_a_crear_pajuela_pesaje_leche(): void
    {
        $this->cambiarRol($this->user);

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->postJson(route('pesaje_leche.store', ['ganado' => $this->ganado->id]), $this->pesoLeche);

        $response->assertStatus(403);
    }

    public function test_veterinario_no_autorizado_a_actualizar_pajuela_pesaje_leche(): void
    {
        $this->cambiarRol($this->user);

        $pajuelasToro = $this->generarPesajesLeche();
        $idRandom = random_int(0, $this->cantidad_pesoLeche - 1);
        $idPesoLecheEditar = $pajuelasToro[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->putJson(route('pesaje_leche.update', ['ganado' => $this->ganado->id,'pesaje_leche' => $idPesoLecheEditar]), $this->pesoLeche);

        $response->assertStatus(403);
    }


    public function test_veterinario_no_autorizado_a_eliminar_pajuela_pesaje_leche(): void
    {
        $this->cambiarRol($this->user);

        $pajuelasToro = $this->generarPesajesLeche();
        $idRandom = random_int(0, $this->cantidad_pesoLeche - 1);
        $idPajuelaToroEliminar = $pajuelasToro[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->deleteJson(route('pesaje_leche.destroy', ['ganado' => $this->ganado->id,'pesaje_leche' => $idPajuelaToroEliminar]));

        $response->assertStatus(403);
    }
}
