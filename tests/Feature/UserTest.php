<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private array $usuario = [
        'usuario' => 'test',
        'password' => '12345678'
    ];


    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();
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


    public function test_obtener_usuario(): void
    {

        $response = $this->actingAs($this->user)->getJson(sprintf('api/usuario/%s', $this->user->id));

        $response->assertStatus(200)->assertJson(['user' => true]);
    }

    public function test_actualizar_usuario(): void
    {

        $response = $this->actingAs($this->user)->putJson(sprintf('api/usuario/%s', $this->user->id), $this->usuario);

        $response->assertStatus(200)->assertJson(['user' => true]);
    }

    public function test_eliminar_usuario(): void
    {

        $response = $this->actingAs($this->user)->deleteJson(sprintf('api/usuario/%s', $this->user->id));

        $response->assertStatus(200)->assertJson(['userID' => $this->user->id]);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_usuario($user, $errores): void
    {
        User::factory()->create(['usuario' => 'test']);

        $response = $this->postJson('api/register', $user);

        $response->assertStatus(422)->assertInvalid($errores);
    }

    public function test_autorizacion_maniupular__usuario_otro_usuario(): void
    {
        $otroUsuario = User::factory()->create();

        $response = $this->actingAs($this->user)->putJson(sprintf('api/usuario/%s', $otroUsuario->id), $this->usuario);

        $response->assertStatus(403);
    }
}
