<?php

namespace Tests\Feature;

use App\Models\RespuestasSeguridad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Support\Str;

use Tests\TestCase;

class RestablecerContraseñaTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->hasConfiguracion()->create();

        $this->user->assignRole('admin');
    }

    public function generar_token($expirado=false): string
    {
        $token=Str::random(64);

        //generar token para restablecer contraseña
        DB::table('password_reset_tokens')->insert([
            'usuario' => $this->user->usuario,
            'token' =>$token,
            'created_at' => $expirado ? now()->subMinutes(20) : now(),
          ]);

        return $token;
    }


    public function generarPreguntasSeguridad(): void
    {
        //las respuestas deben estar cifradas
        RespuestasSeguridad::factory()
            ->count(3)
            ->for($this->user)
            ->sequence(
                ['respuesta'=>Hash::make('perro')],
                ['respuesta'=>Hash::make('abuelo')],
                ['respuesta'=>Hash::make('favorito')],
                )
            ->create();
    }



      public function test_buscar_usuario_para_restablecer_contraseña(): void
    {
       $this->generarPreguntasSeguridad();

        $response = $this->postJson(route('restablecerContraseña.buscarUsuario'), [
            'usuario' => $this->user->usuario,
        ]);

        $response->assertStatus(200)->assertJson(fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json->whereAllType(['token' => 'string','preguntas' => 'array'])
            ->has('preguntas.0', fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json->whereAllType(['id' => 'integer','pregunta' => 'string'])
            )
    );
    }


    public function test_error_buscar_usuario_sin_preguntas_seguridad_para_restablecer_contraseña(): void
    {
        $response = $this->postJson(route('restablecerContraseña.buscarUsuario'), [
            'usuario' => $this->user->usuario,
        ]);

        $response->assertStatus(403)->assertJson(fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json->where('message','El usuario no tiene preguntas de seguridad')
    );
    }


     public function test_restablecer_contraseña(): void
    {
      $token=$this->generar_token();

        $this->generarPreguntasSeguridad();

        $response = $this->postJson(route('restablecerContraseña.restablecerContraseña',['token'=>$token]), [
            'password' => '12345678',
            'respuestas' => ['perro','abuelo','favorito'],
        ]);

        $response->assertStatus(200)->assertJson(['message' => 'Contraseña restablecida exitosamente']);
    }

    public function test_restablecer_contraseña_con_insensible_a_mayúscula_y_minúscula(): void
    {
      $token=$this->generar_token();

        $this->generarPreguntasSeguridad();

        $response = $this->postJson(route('restablecerContraseña.restablecerContraseña',['token'=>$token]), [
            'password' => '12345678',
            'respuestas' => ['perro','ABUELO','Favorito'],
        ]);

        $response->assertStatus(200)->assertJson(['message' => 'Contraseña restablecida exitosamente']);
    }


    public function test_error_restablecer_contraseña_porque_se_envian_respuestas_incorrectas(): void
    {
      $token=$this->generar_token();

      $this->generarPreguntasSeguridad();

        $response = $this->postJson(route('restablecerContraseña.restablecerContraseña',['token'=>$token]), [
            'password' => '12345678',
            'respuestas' => ['perrito','abuelo','favorito'],
        ]);

        $response->assertStatus(403)->assertJson(['message' => 'respuestas incorrectas']);
    }


    public function test_error_porque_se_envia_un_token_distinto_al_generado_para_restablecer_contraseña(): void
    {
      $token=$this->generar_token();

      $this->generarPreguntasSeguridad();

        $response = $this->postJson(route('restablecerContraseña.restablecerContraseña',['token'=>Str::random(13)]), [
            'password' => '12345678',
            'respuestas' => ['perrito','abuelo','favorito'],
        ]);

        $response->assertStatus(403)->assertJson(['message' => 'Token no encontrado']);
    }

    public function test_error_se_envia_un_token_expirado_para_restablecer_contraseña(): void
    {
      $token=$this->generar_token(true);

      $this->generarPreguntasSeguridad();

        $response = $this->postJson(route('restablecerContraseña.restablecerContraseña',['token'=>$token]), [
            'password' => '12345678',
            'respuestas' => ['perrito','abuelo','favorito'],
        ]);

        $response->assertStatus(403)->assertJson(['message' => 'Token expirado, porfavor vuelva a realizar la operacion']);
    }

    public function test_se_eliminan_tokens_previos_inconclusos_al_buscar_usuario_para_restablecer_contraseña(): void
    {
        $this->generar_token();

        $this->generarPreguntasSeguridad();

        //buscar usuario para restablecer contraseña
        $this->postJson(route('restablecerContraseña.buscarUsuario'), [
            'usuario' => $this->user->usuario,
        ]);

        /* dd(DB::table('password_reset_tokens')->get()); */

        //solo debe de haber 1, ya que se elimina el que se creo y solo debe estar el que se acaba de crear cuando se busca un usuario
        $this->assertDatabaseCount('password_reset_tokens', 1);

    }

    public function test_se_elimina_token_al_restablecer_contraseña(): void
    {
        $token=$this->generar_token();

        $this->generarPreguntasSeguridad();

        $response = $this->postJson(route('restablecerContraseña.restablecerContraseña',['token'=>$token]), [
            'password' => '12345678',
            'respuestas' => ['perro','abuelo','favorito'],
        ]);


        //solo debe de haber 1, ya que se elimina el que se creo y solo debe estar el que se acaba de crear cuando se busca un usuario
        $this->assertDatabaseCount('password_reset_tokens', 0);

    }

}
