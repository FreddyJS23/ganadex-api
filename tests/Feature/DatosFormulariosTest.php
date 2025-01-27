<?php

namespace Tests\Feature;

use App\Models\Comprador;
use App\Models\Estado;
use App\Models\Finca;
use App\Models\Ganado;
use App\Models\Leche;
use App\Models\Personal;
use App\Models\User;
use App\Models\UsuarioVeterinario;
use App\Models\Vacuna;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class DatosFormulariosTest extends TestCase
{
    use RefreshDatabase;
    private $user;
    private int $cantidad_ganado = 50;
    private $estado;
    private $finca;
    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();

            $this->finca
            = Finca::factory()
            ->hasAttached($this->user)
            ->create();

        $this->estado = Estado::all();
    }
    private function generarGanado(): Collection
    {
        return Ganado::factory()
            ->count($this->cantidad_ganado)
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->finca)
            ->create();
    }

    /**
     * A basic feature test example.
     */

     public function test_obtener_novillas_que_se_pueden_servir()
     {
        $this->generarGanado();
        $response = $this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->getJson(route('datosParaFormularios.novillasParaMontar'));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->whereType('novillas_para_servicio', 'array')
                ->where('novillas_para_servicio', fn (SupportCollection $novillasParaServir) => count($novillasParaServir) > 1  ? true : false)
                ->has(
                    'novillas_para_servicio.0',
                    fn (AssertableJson $json)
                    => $json->whereAllType([
                        'id' => 'integer',
                        'numero' => 'integer',
                        'peso_actual'=>'string'
                    ])
                )
        );
     }

     public function test_obtener_años_de_ventas_de_ganados()
     {
         Venta::factory()
            ->count(10)
            ->for($this->finca)
            ->for(Ganado::factory()->for($this->finca)->hasPeso(1)->hasAttached($this->estado)->create())
            ->for(Comprador::factory()->for($this->finca)->create())
            ->create();

            $response=$this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->getJson(route('datosParaFormularios.añosVentasGanado'));

            $response->assertStatus(200)->assertJson(
                fn (AssertableJson $json) =>
                $json->whereType('años_ventas_ganado', 'array')
                    ->has(
                        'años_ventas_ganado.0',
                        fn (AssertableJson $json)
                        => $json->whereAllType([
                            'año' => 'integer',
                        ])
                    )
            );
     }
     public function test_obtener_años_de_produccion_de_leches()
     {
        Leche::factory()
        ->count(10)
        ->for(Ganado::factory()->for($this->finca)->hasPeso(1)->hasAttached($this->estado)->create())
        ->for($this->finca)
        ->create();

            $response=$this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->getJson(route('datosParaFormularios.añosProduccionLeche'));

            $response->assertStatus(200)->assertJson(
                fn (AssertableJson $json) =>
                $json->whereType('años_produccion_leche', 'array')
                    ->has(
                        'años_produccion_leche.0',
                        fn (AssertableJson $json)
                        => $json->whereAllType([
                            'año' => 'integer',
                        ])
                    )
            );
     }

     public function test_obtener_vacunas_disponibles(){
        Vacuna::factory()
        ->count(10)
        ->create();

        $response=$this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->getJson(route('datosParaFormularios.vacunasDisponibles'));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->whereType('vacunas_disponibles', 'array')
                ->has(
                    'vacunas_disponibles.0',
                    fn (AssertableJson $json)
                    => $json->whereAllType([
                        'id'=>'integer',
                        'nombre'=>'string',
                        'intervalo_dosis'=>'integer',
                        'tipo_animal'=>'array'
                    ])
                )
        );

     }

     public function test_obtener_numero_disponible_en_DB()
     {
        $this->generarGanado();

        $response=$this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->getJson(route('datosParaFormularios.sugerirNumeroDisponibleEnBD'));

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json)=>
            $json->whereType('numero_disponible', 'integer')
        );

     }

     public function test_obtener_veterinarios_sin_usuario(): void{

        UsuarioVeterinario::factory()
        ->count(10)
        ->for(Personal::factory()->for($this->finca)->create(['cargo_id' => 2]), 'veterinario')
        ->create(['admin_id' => $this->user->id]);

        Personal::factory()
            ->count(10)
            ->for($this->finca)
            ->create(['cargo_id' => 2]);

        $response=$this->actingAs($this->user)->withSession(['finca_id' => $this->finca->id])->getJson(route('datosParaFormularios.veterinariosSinUsuario'));

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json)=>
            $json->whereType('veterinarios_sin_usuario', 'array')
                ->has(
                    'veterinarios_sin_usuario',
                    10,
                    fn (AssertableJson $json)
                    => $json->whereAllType([
                        'id'=>'integer',
                        'nombre'=>'string',
                    ])
                )
        );
     }
}
