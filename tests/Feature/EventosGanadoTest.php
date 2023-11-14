<?php

namespace Tests\Feature;

use App\Models\Comprador;
use App\Models\Estado;
use App\Models\Fallecimiento;
use App\Models\Ganado;
use App\Models\Parto;
use App\Models\Toro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;
use Illuminate\Support\Str;

class EventosGanadoTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $toro;
    private $ganado;
    private $estado;
    private $numero_toro;
    private int $cantidad_ganado = 50;

    private array $servicio = [
        'observacion' => 'bien',
        'tipo' => 'Monta'
    ];

    private $parto = [
        'observacion' => 'bien',
        'nombre' => 'test',
        'numero' => 33,
        'peso_nacimiento' => '50KG',
    ];
    private $hembra = ['sexo' => 'H'];
    private $macho = ['sexo' => 'M'];



    private array $revision = [
        'diagnostico' => 'prenada',
        'tratamiento' => 'medicina',
    ];

    private array $venta = [
        'precio' => 350,
    ];
    
    private array $fallecimiento = [
        'causa' => 'enferma',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();

        $this->estado = Estado::all();

        $this->ganado
            = Ganado::factory()
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->user)
            ->create(['sexo' => 'H', 'tipo_id' => 3]);

        $this->toro = Toro::factory()
            ->for($this->user)
            ->for(Ganado::factory()->for($this->user)->create(['sexo' => 'M']))->create();

        $this->numero_toro = $this->toro->ganado->numero;
    }

    /**
     * A basic feature test example.
     */
    public function test_cuando_se_realiza_un_servicio(): void
    {
        //realizar servicio
        $this->actingAs($this->user)->postJson(
            sprintf('api/ganado/%s/servicio', $this->ganado->id),
            $this->servicio + ['numero_toro' => $this->numero_toro]
        );
        $response = $this->actingAs($this->user)->getJson(sprintf('api/ganado/%s', $this->ganado->id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->whereAllType([
                'ganado.prox_revision' => 'string',
                'servicio_reciente' => 'array',
                'total_servicios' => 'integer'
            ])->etc()
        );
    }

    public function test_cuando_se_realiza_una_revision_y_sale_preñada(): void
    {
        //realizar servicio
        $this->actingAs($this->user)->postJson(
            sprintf('api/ganado/%s/servicio', $this->ganado->id),
            $this->servicio + ['numero_toro' => $this->numero_toro]
        );

        //realizar revision
        $this->actingAs($this->user)->postJson(
            sprintf('api/ganado/%s/revision', $this->ganado->id),
            $this->revision
        );

        $response = $this->actingAs($this->user)->getJson(sprintf('api/ganado/%s', $this->ganado->id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->whereAllType(
                [
                    'ganado.prox_parto' => 'string',
                    'ganado.prox_secado' => 'string',
                    'revision_reciente' => 'array',
                    'total_revisiones' => 'integer',
                ]
            )->has('ganado.estados',3)
                ->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'pendiente_secar')
                )
                ->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'gestacion')
                )
                ->etc()
        );
    }


    public function test_cuando_se_realiza_un_parto_empieza_lactancia_y_cambia_adulto(): void
    {
        //realizar servicio
        $this->actingAs($this->user)->postJson(
            sprintf('api/ganado/%s/servicio', $this->ganado->id),
            $this->servicio + ['numero_toro' => $this->numero_toro]
        );

        //realizar parto
        $this->actingAs($this->user)->postJson(
            sprintf('api/ganado/%s/parto', $this->ganado->id),
            $this->parto + $this->hembra
        );

        $response = $this->actingAs($this->user)->getJson(sprintf('api/ganado/%s', $this->ganado->id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json->whereAllType(
                [
                    'ganado.prox_revision' => 'string',
                    'servicio_reciente' => 'array',
                    'total_servicios' => 'integer',
                    'parto_reciente' => 'array',
                    'parto_reciente.cria' => 'array',
                    'total_partos' => 'integer',
                ]
            )->has('ganado.estados', 2)
                ->where('ganado.tipo', 'adulto')
                ->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'sano')

                )->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'lactancia')
                )
                ->etc()
        );
    }

    public function test_cuando_se_realiza_un_parto_y_nace_macho(): void
    {
        //realizar servicio
        $this->actingAs($this->user)->postJson(
            sprintf('api/ganado/%s/servicio', $this->ganado->id),
            $this->servicio + ['numero_toro' => $this->numero_toro]
        );

        //realizar parto
        $this->actingAs($this->user)->postJson(
            sprintf('api/ganado/%s/parto', $this->ganado->id),
            $this->parto + $this->macho
        );

        $cria_id = Parto::select('ganado_cria_id')->where('ganado_id', $this->ganado->id)->first();

        $response = $this->actingAs($this->user)->getJson(sprintf('api/ganado/%s', $cria_id->ganado_cria_id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json
                ->has('ganado.estados', 3)    
            ->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'pendiente_numeracion')

                )->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'pendiente_capar')

                )->etc()
        );
    }

    public function test_cuando_se_realiza_un_parto_la_cria_hembra_tiene_que_estar_pendiente_numeracion(): void
    {
        //realizar servicio
        $this->actingAs($this->user)->postJson(
            sprintf('api/ganado/%s/servicio', $this->ganado->id),
            $this->servicio + ['numero_toro' => $this->numero_toro]
        );

        //realizar parto
        $this->actingAs($this->user)->postJson(
            sprintf('api/ganado/%s/parto', $this->ganado->id),
            $this->parto + $this->hembra
        );

        $cria_id = Parto::select('ganado_cria_id')->where('ganado_id', $this->ganado->id)->first();

        $response = $this->actingAs($this->user)->getJson(sprintf('api/ganado/%s', $cria_id->ganado_cria_id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json
                ->has('ganado.estados', 2)    
            ->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'pendiente_numeracion')

                )->etc()
        );
    }

    public function test_cuando_se_realiza_una_venta(): void
    {
        $comprador = Comprador::factory()->for($this->user)->create();
        $this->venta = $this->venta + ['ganado_id' => $this->ganado->id, 'comprador_id' => $comprador->id];

        //realizar venta
        $this->actingAs($this->user)->postJson(route('ventas.store'), $this->venta);


        $response = $this->actingAs($this->user)->getJson(sprintf('api/ganado/%s', $this->ganado->id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json
                ->has('ganado.estados', 1)    
            ->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado', 'vendido')

                )->etc()
        );
    }
    public function test_cuando_se_registra_fallecimiento_de_una_cabeza_ganado(): void
    {
      
        $this->fallecimiento = $this->fallecimiento + ['numero_ganado' => $this->ganado->numero];

        //registrar fallecimiento
        $this->actingAs($this->user)->postJson(route('fallecimientos.store'), $this->fallecimiento);


        $response = $this->actingAs($this->user)->getJson(sprintf('api/ganado/%s', $this->ganado->id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json
                ->has('ganado.estados', 1)    
            ->where(
                    'ganado.estados',
                    fn (Collection $estados) => $estados->contains('estado','fallecido')

                )->etc()
        );
    }
}
