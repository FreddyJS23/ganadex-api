<?php

namespace Tests\Feature;

use App\Models\Comprador;
use App\Models\Ganado;
use App\Models\Parto;
use App\Models\Toro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;
use Illuminate\Support\Str;

class EventosGanadoTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $toro;
    private $ganado;
    private $numero_toro;
    private int $cantidad_ganado = 50;

    private array $servicio = [
        'observacion' => 'bien',
        'tipo' => 'monta'
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();


        $this->ganado
            = Ganado::factory()
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasEstado(1)
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
            'ganado.prox_revision'=>'string',
            'servicio_reciente'=>'array',
            'total_servicios'=>'integer'])->etc()
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
            )->where(
                'ganado.estado',
                fn (string $estado) => Str::containsAll($estado, ['gestacion', 'pendiente_secar'])
            )->etc()
        );
    }


    public function test_cuando_se_realiza_un_parto_y_nace_hembra(): void
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
            fn (AssertableJson $json) => $json->whereAllType([
                'ganado.prox_revision' => 'string',
                'servicio_reciente' => 'array',
                'total_servicios' => 'integer',
                'parto_reciente'=>'array',
                'parto_reciente.cria'=>'array',
                'total_partos' => 'integer',
                ]
            )->where(
                'ganado.estado',
                fn (string $estado) => Str::containsAll($estado, ['lactancia'])
            )->where('ganado.tipo', 'adulto')->etc()
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
                ->where(
                    'ganado.estado',
                    fn (string $estado) => Str::containsAll($estado, ['-pendiente_capar','-pendiente_numeracion'])
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
                ->where(
                    'ganado.estado',
                    fn (string $estado) => Str::containsAll($estado, ['-pendiente_numeracion'])
                )->etc()
        );
    }

    public function test_cuando_se_realiza_una_venta(): void
    {
        $comprador = Comprador::factory()->for($this->user)->create();
        $this->venta=$this->venta + ['ganado_id'=>$this->ganado->id,'comprador_id'=>$comprador->id];
        
        //realizar venta
       $this->actingAs($this->user)->postJson(route('ventas.store'), $this->venta);
       
       
       $response = $this->actingAs($this->user)->getJson(sprintf('api/ganado/%s', $this->ganado->id));

        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) => $json
                ->where(
                    'ganado.estado',
                    fn (string $estado) =>Str::containsAll($estado, ['vendida'])
                )->etc()
        );
    }
}
