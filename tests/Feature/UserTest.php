<?php

namespace Tests\Feature;

use App\Models\Hacienda;
use App\Models\Personal;
use App\Models\User;
use App\Models\UsuarioVeterinario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private array $usuario = [
        'usuario' => 'test',
        'password' => '12345678'
    ];

    private $hacienda;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->hasConfiguracion()->create();

            $this->hacienda
            = Hacienda::factory()
            ->for($this->user)
            ->create();

        $this->user->assignRole('admin');

        UsuarioVeterinario::factory()
        ->count(10)
        ->for(Personal::factory()->for($this->user)->create(['cargo_id' => 2]), 'veterinario')
        ->create(['admin_id' => $this->user->id]);
    }

    public static function ErrorInputProvider(): array
    {
        return [
            'caso de que exista el nombre o numero' => [
                [
                    'usuario' => 'test',
                    'password' => '12345678',

                ], ['usuario']
            ],
            'caso de insertar datos erróneos' => [
                [
                    'usuario' => 'te',
                    'password' => '12',
                ],
                ['usuario', 'password']
            ],
            'caso de no insertar datos requeridos' => [
                [], ['usuario', 'password']
            ],
        ];
    }



    /**
     * A basic feature test example.
     */


    public function test_creacion_usuario(): void
    {
        $response = $this->withSession(['user' => true])->postJson('api/register', $this->usuario);

        $response->assertStatus(201)->assertJson(['message' => 'usuario creado']);
    }


    public function test_obtener_usuario_administrador(): void
    {

        $response = $this->actingAs($this->user)->getJson(route('usuario.show', ['user' => $this->user->id]));
        $response->assertStatus(200)
           ->assertJson(
               fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                   'user',
                   fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                   $json->whereAllType([
                       'id' => 'integer',
                       'usuario' => 'string',
                       'email' => 'string',
                       'rol' => 'string',
                       'haciendas' => 'array',
                       'fecha_creacion' => 'string',
                       'configuracion' => 'array',
                       'tiene_preguntas_seguridad' => 'boolean',
                   ])
               )
           );
    }

    public function test_obtener_usuario_veterinario(): void
    {
        $this->user->syncRoles('veterinario');

        $response = $this->actingAs($this->user)->getJson(route('usuario.show', ['user' => $this->user->id]));
        $response->assertStatus(200)
            ->assertJson(
                fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has(
                    'user',
                    fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                    $json->whereAllType([
                        'id' => 'integer',
                        'usuario' => 'string',
                        'email' => 'string',
                        'rol' => 'string',
                        'fecha_creacion' => 'string',
                        'tiene_preguntas_seguridad' => 'boolean',

                    ])
                )
            );
    }


    public function test_actualizar_usuario(): void
    {

        $response = $this->actingAs($this->user)->putJson(route('usuario.update', ['user' => $this->user->id]), $this->usuario);

        $response->assertStatus(200)->assertJson(['user' => true]);
    }

    public function test_actualizar_usuario_con_otro_existente_repitiendo_campos_unicos(): void
    {
        $usuarioExistente = User::factory()->create(['usuario' => 'test']);

        $response = $this->actingAs($this->user)->putJson(route('usuario.update', ['user' => $this->user->id]), $this->usuario);

        $response->assertStatus(422)->assertJson(fn (AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
        $json->hasAll(['errors.usuario'])
        ->etc());
    }

    public function test_actualizar_usuario_conservando_campos_unicos(): void
    {
        $otroUsuario = User::factory()->create(['usuario' => 'test']);

        $otroUsuario->syncRoles('admin');

        $response = $this->actingAs($otroUsuario)->putJson(route('usuario.update', ['user' => $otroUsuario->id]), $this->usuario);

        $response->assertStatus(200);
    }


    public function test_eliminar_usuario(): void
    {

        $response = $this->actingAs($this->user)->deleteJson(route('usuario.destroy', ['user' => $this->user->id]));

        $response->assertStatus(200)->assertJson(['userID' => $this->user->id]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_usuario(array $user, array $errores): void
    {
        User::factory()->create(['usuario' => 'test']);

        $response = $this->postJson('api/register', $user);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_autorizacion_maniupular__usuario_otro_usuario(): void
    {
        $otroUsuario = User::factory()->create();

        $response = $this->actingAs($this->user)->putJson(route('usuario.update', ['user' => $otroUsuario->id]), $this->usuario);

        $response->assertStatus(403);
    }

    public function test_autorizacion_ver_informacion_de_otro_usuario(): void
    {
        $otroUsuario = User::factory()->create();

        $response = $this->actingAs($this->user)->putJson(route('usuario.show', ['user' => $otroUsuario->id]), $this->usuario);

        $response->assertStatus(403);
    }
}
