<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Ganado;
use App\Models\Hacienda;
use App\Models\Personal;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class HaciendaTest extends TestCase
{
    use RefreshDatabase;

    private array $hacienda = [
        'nombre' => 'hacienda test',
    ];

    private $haciendaEnSesion;

    private int $cantidad_haciendas = 10;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->hasConfiguracion()->create();

            $this->user->assignRole('admin');

            $this->haciendaEnSesion
            = Hacienda::factory()
            ->for($this->user)
            ->create(['nombre' => 'hacienda_sesion']);
    }

    private function generarHaciendas(): Collection
    {
        return Hacienda::factory()
            ->count($this->cantidad_haciendas)
            ->for($this->user)
            ->create();
    }
    public static function ErrorInputProvider(): array
    {
        return [
            'caso de que exista la hacienda' => [
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



    public function test_obtener_haciendas_usuario(): void
    {
        $this->generarHaciendas();

        $response = $this->actingAs($this->user)->getJson(route('hacienda.index'));

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json->has(
                'haciendas',
                $this->cantidad_haciendas + 1,
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                => $json->whereAllType([
                    'id' => 'integer',
                    'nombre' => 'string',
                    'fecha_creacion' => 'string'
                ])
            )
        );
    }


     public function test_creacion_hacienda_y_tiene_varias_anteriores(): void
    {
        $this->generarHaciendas();

        $response = $this->actingAs($this->user)->postJson(route('hacienda.store'), $this->hacienda);

        $response->assertStatus(201)->assertJson(
            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json->has(
                'hacienda',
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                => $json->whereAllType([
                    'id' => 'integer',
                    'nombre' => 'string',
                    'fecha_creacion' => 'string'
                ])
            )
        )->assertSessionMissing('hacienda_id');
    }

    public function test_creacion_hacienda_por_primera_vez(): void
    {
        //vaciar tabla hacienda
        //no se usa truncate ya que da error de contricciones de llaves foraneas
        Hacienda::where('id', '>', 0)->delete();

        $response = $this->actingAs($this->user)->postJson(route('hacienda.store'), $this->hacienda);

        $response->assertStatus(201)->assertJson(
            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json->has(
                'hacienda',
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                => $json->whereAllType([
                    'id' => 'integer',
                    'nombre' => 'string',
                    'fecha_creacion' => 'string'
                ])
            )
        )->assertSessionHas('hacienda_id', Hacienda::all()->first()->id);
    }

    public function test_actualizar_hacienda(): void
    {
        $hacienda = $this->generarHaciendas();
        $idRandom = random_int(0, $this->cantidad_haciendas - 1);
        $idHaciendaEditar = $hacienda[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $idHaciendaEditar])->putJson(route('hacienda.update', ['hacienda' => $idHaciendaEditar]), $this->hacienda);

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
             $json->has(
                 'hacienda',
                 fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                 $json->where('id', $idHaciendaEditar)
                 ->where('nombre', $this->hacienda['nombre'])
                 ->etc()
             )
        );
    }

    public function test_actualizar_hacienda_con_otro_existente_repitiendo_campos_unicos(): void
    {
        $haciendaExistente = hacienda::factory()->for($this->user)->create();

        $hacienda = $this->generarHaciendas();
        $idRandom = random_int(0, $this->cantidad_haciendas - 1);
        $idhaciendaEditar = $hacienda[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $idhaciendaEditar])->putJson(route('hacienda.update', ['hacienda' => $hacienda[$idRandom]]), ['nombre' => 'hacienda_sesion']);

        $response->assertStatus(422)->assertJson(fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
        $json->hasAll(['errors.nombre'])->etc());
    }

    public function test_admin_obtiene_hacienda_en_sesion(): void
    {
        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->haciendaEnSesion->id])->getJson(route('verificar_sesion_hacienda'));

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json->has(
                'hacienda',
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                => $json->whereAllType([
                    'id' => 'integer',
                    'nombre' => 'string',
                    'fecha_creacion' => 'string'
                ])
            )
        );
    }

    public function test_veterinario_obtiene_hacienda_en_sesion(): void
    {
        $this->user->syncRoles('veterinario');

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->haciendaEnSesion->id])->getJson(route('verificar_sesion_hacienda'));

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json->has(
                'hacienda',
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                => $json->whereAllType([
                    'id' => 'integer',
                    'nombre' => 'string',
                    'fecha_creacion' => 'string'
                ])
            )
        );
    }

    public function test_creacion_sesion_hacienda(): void
    {

        $response = $this->actingAs($this->user)->getJson(route('crear_sesion_hacienda', ['hacienda' => $this->haciendaEnSesion]));

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json->has(
                'hacienda',
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                => $json->whereAllType([
                    'id' => 'integer',
                    'nombre' => 'string',
                    'fecha_creacion' => 'string'
                ])
            )
        );
    }

    public function test_admin_cambia_sesion_hacienda(): void
    {
        $hacienda = $this->generarHaciendas();
        $idRandom = random_int(0, $this->cantidad_haciendas - 1);
        $idHacienda = $hacienda[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->haciendaEnSesion->id])->getJson(route('cambiar_hacienda_sesion',['hacienda' => $idHacienda]));

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json->has(
                'hacienda',
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                => $json->whereAllType([
                    'id' => 'integer',
                    'nombre' => 'string',
                    'fecha_creacion' => 'string'
                ])
                ->where('id', $idHacienda)
            )
        );
    }

    public function test_veterinario_cambia_sesion_hacienda(): void
    {
        $hacienda = $this->generarHaciendas();
        $idRandom = random_int(0, $this->cantidad_haciendas - 1);
        $idHacienda = $hacienda[$idRandom]->id;

        $this->user->syncRoles('veterinario');

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->haciendaEnSesion->id])->getJson(route('cambiar_hacienda_sesion',['hacienda' => $idHacienda]));

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json->has(
                'hacienda',
                fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                => $json->whereAllType([
                    'id' => 'integer',
                    'nombre' => 'string',
                    'fecha_creacion' => 'string'
                ])
                ->where('id', $idHacienda)
            )
        );
    }

    public function test_error_no_hay_sesion_hacienda_y_intenta_cambiar_sesion_hacienda(): void
    {
        $hacienda = $this->generarHaciendas();
        $idRandom = random_int(0, $this->cantidad_haciendas - 1);
        $idHacienda = $hacienda[$idRandom]->id;


        $response = $this->actingAs($this->user)->getJson(route('cambiar_hacienda_sesion',['hacienda' => $idHacienda]));

        $response->assertStatus(403);
    }

    public function test_error_no_existe_hacienda_y_intenta_cambiar_sesion_hacienda(): void
    {
        $hacienda = $this->generarHaciendas();
        $idRandom = random_int(0, $this->cantidad_haciendas - 1);
        $idHacienda = $hacienda[$idRandom]->id;


        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->haciendaEnSesion->id])->getJson(route('cambiar_hacienda_sesion',['hacienda' => 866969]));

        $response->assertStatus(404);
    }

    public function test_error_creacion_sesion_hacienda_otro_usuario(): void
    {
        $otroUsuario = User::factory()->create();

        $haciendaOtroUsuario = hacienda::factory()->for($otroUsuario)->create();

        $idhaciendaOtroUsuario = $haciendaOtroUsuario->id;

        $response = $this->actingAs($this->user)->getJson(route('crear_sesion_hacienda', ['hacienda' => $idhaciendaOtroUsuario]));

        $response->assertStatus(403);
    }


    /**
     * @dataProvider ErrorinputProvider
     */
     public function test_error_validacion_registro_hacienda(array $hacienda, array $errores): void
    {
        $haciendaTest = Hacienda::factory()->for($this->user)->create(['nombre' => 'test']);

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $haciendaTest->id])->postJson(route('hacienda.store'), $hacienda);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_eliminar_hacienda(): void
    {
        $hacienda = $this->generarHaciendas();
        $idRandom = random_int(0, $this->cantidad_haciendas - 1);
        $idToDelete = $hacienda[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->haciendaEnSesion->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->deleteJson(route('hacienda.destroy', ['hacienda' => $idToDelete]));

        $response->assertStatus(200)->assertJson(['haciendaID' => $idToDelete]);
    }


    public function test_error_eliminar_hacienda_con_registros(): void
    {
        $hacienda = $this->generarHaciendas();
        $idRandom = random_int(0, $this->cantidad_haciendas - 1);
        $idToDelete = $hacienda[$idRandom]->id;
        $estado = Estado::find(1);

        Ganado::factory()
        ->hasPeso(1)
        ->hasEvento(1)
        ->hasAttached($estado)
        ->create(['hacienda_id' => $idToDelete]);

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->haciendaEnSesion->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->deleteJson(route('hacienda.destroy', ['hacienda' => $idToDelete]));

        $response->assertStatus(403)->assertJson(['message' => 'No se puede eliminar una hacienda con registros de animales']);
    }

    public function test_error_eliminar_hacienda_otro_usuario(): void
    {
        $otroUsuario = User::factory()->create();

        $haciendaOtroUsuario = hacienda::factory()->for($otroUsuario)->create();

        $idhaciendaOtroUsuario = $haciendaOtroUsuario->id;


        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->haciendaEnSesion->id,'peso_servicio' => $this->user->configuracion->peso_servicio,'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna])->deleteJson(route('hacienda.destroy', ['hacienda' => $idhaciendaOtroUsuario]));

        $response->assertStatus(403);
    }



    public function test_autorizacion_maniupular__hacienda_otro_usuario(): void
    {
        $otroUsuario = User::factory()->create();

        $haciendaOtroUsuario = hacienda::factory()->for($otroUsuario)->create();

        $idhaciendaOtroUsuario = $haciendaOtroUsuario->id;

        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->haciendaEnSesion])->putJson(route('hacienda.update', ['hacienda' => $idhaciendaOtroUsuario]), $this->hacienda);

        $response->assertStatus(403);
    }
}
