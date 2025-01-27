<?php

namespace Tests\Feature;

use App\Models\Finca;
use App\Models\Personal;
use App\Models\User;
use App\Models\UsuarioVeterinario;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class UsuarioVeterinarioTest extends TestCase
{
    use RefreshDatabase;

    private array $usuarioVeterinario;
    private $user;
    private $finca;
    private $personal;


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

        $this->personal
            = Personal::factory()
            ->for($this->finca)
            ->create(['cargo_id' => 2])->id;

        $this->usuarioVeterinario = ['personal_id' => $this->personal];
    }

    public static function ErrorInputProvider(): array
    {
        return [

            'caso de insertar veterinario no registrado' => [
                [
                    'personal_id' => 1943789714
                ],
                ['personal_id']
            ],
            'caso de no insertar datos requeridos' => [
                [],
                ['personal_id']
            ],
        ];
    }


    private function usuariosVeterinarios(): Collection
    {
        return UsuarioVeterinario::factory()
            ->count(10)
            ->for(Personal::factory()->for($this->finca)->create(['cargo_id' => 2]), 'veterinario')
            ->create(['admin_id' => $this->user->id]);
    }


    public function test_obtener_usuarios_veterinarios(): void
    {
        $this->usuariosVeterinarios();

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->getJson(route('usuarios_veterinarios.index'));
        $response->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json) => $json->has(
                    'usuarios_veterinarios',
                    10,
                    fn(AssertableJson $json) => $json->whereAllType([
                        'id' => 'integer',
                        'usuario' => 'string',
                        'nombre' => 'string',
                    ])->where('rol', 'veterinario')
                )
            );
    }


    public function test_creacion_usuario_veterinario(): void
    {
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->postJson(route('usuarios_veterinarios.store'), ['personal_id' => $this->personal]);

        $response->assertStatus(201)->assertJson(
            fn(AssertableJson $json) => $json->has(
                'usuario_veterinario',
                fn(AssertableJson $json) => $json->whereAllType([
                    'id' => 'integer',
                    'usuario' => 'string',
                    'nombre' => 'string',
                ])
                    ->where('rol', 'veterinario')
            )
        );
    }

    public function test_creacion_usuario_veterinario_con_nombre_veterinario_muy_largo(): void
    {
        $this->personal
        = Personal::factory()
        ->for($this->finca)
        ->create(['nombre'=>'sdsdsfdsfjijwiwwjkhkjbjhkgiggyg','cargo_id' => 2])->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->postJson(route('usuarios_veterinarios.store'), ['personal_id' => $this->personal]);

        $response->assertStatus(201)->assertJson(
            fn(AssertableJson $json) => $json->has(
                'usuario_veterinario',
                fn(AssertableJson $json) => $json
                ->where('usuario',fn(string $usuario)=> str_contains($usuario,'usuario') )
                ->etc()
            )
        );
    }
    public function test_creacion_usuario_veterinario_con_nombre_veterinario_muy_corto(): void
    {
        $this->personal
        = Personal::factory()
        ->for($this->finca)
        ->create(['nombre'=>'s','cargo_id' => 2])->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->postJson(route('usuarios_veterinarios.store'), ['personal_id' => $this->personal]);

        $response->assertStatus(201)->assertJson(
            fn(AssertableJson $json) => $json->has(
                'usuario_veterinario',
                fn(AssertableJson $json) => $json
                ->where('usuario',fn(string $usuario)=> str_contains($usuario,'usuario') )
                ->etc()
            )
        );
    }

    public function test_eliminar_usuario_veterinario(): void
    {
        $usuarioVeterinario = $this->usuariosVeterinarios();
        $idRandom = rand(0, 9);
        $idToDelete = $usuarioVeterinario[$idRandom]->id;

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->deleteJson(route('usuarios_veterinarios.destroy', ['usuarios_veterinario' => $idToDelete]));

        $response->assertStatus(200)->assertJson(['usuarioVeterinarioID' => $idToDelete]);
    }


    public function test_sin_autorizacion_eliminar_usuario_otro_administrador(): void
    {
        $otroFinca = Finca::factory()
            ->hasAttached($this->user)
            ->create(['nombre' => 'otro_finca']);

        $otroAdmin = User::factory()->create(['usuario' => 'test']);

        $otroAdmin->assignRole('admin');

        $usuarioVeterinarioOtroAdmin =  UsuarioVeterinario::factory()
            ->for(Personal::factory()->for($otroFinca)->create(), 'veterinario')
            ->create(['admin_id' => $otroAdmin->id]);

        $idToDelete = $usuarioVeterinarioOtroAdmin->id;

        $this->usuariosVeterinarios();


        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])
            ->withSession(['finca_id' => $this->finca->id])->deleteJson(route('usuarios_veterinarios.destroy', ['usuarios_veterinario' => $idToDelete]));

        $response->assertStatus(403);
    }


    public function test__usuario_veterinario_sin_autorizacion_crear_usuario_veterinario(): void
    {
        $this->user->syncRoles('veterinario');

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->postJson(route('usuarios_veterinarios.store'), ['personal_id' => $this->personal]);

        $response->assertStatus(403);
    }

    public function test__usuario_veterinario_sin_autorizacion_ver_usuarios_veterinario(): void
    {
        $this->user->syncRoles('veterinario');

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->getJson(route('usuarios_veterinarios.index'));

        $response->assertStatus(403);
    }

    /**
     * @dataProvider ErrorinputProvider
     */
    public function test_error_validacion_registro_usuario_veterinario($usuarioVeterinario, $errores): void
    {

        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->postJson(route('usuarios_veterinarios.store'), $usuarioVeterinario);

        $response->assertStatus(422)->assertInvalid($errores);
    }
}
