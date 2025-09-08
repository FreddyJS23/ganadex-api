<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Hacienda;
use App\Models\Ganado;
use App\Models\Parto;
use App\Models\PartoCria;
use App\Models\Personal;
use App\Models\Toro;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Tests\Feature\Common\NeedsEstado;
use Tests\Feature\Common\NeedsGanado;
use Tests\Feature\Common\NeedsPersonal;
use Tests\Feature\Common\NeedsSetupRequest;
use Tests\Feature\Common\NeedsToro;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class ResumenesAnualesTest extends TestCase
{
    use NeedsSetupRequest,
        NeedsPersonal,
        NeedsGanado,
        NeedsEstado,
        NeedsToro {
        NeedsSetupRequest::setUp as needsSetupRequestSetUp;
        NeedsEstado::setUp as needsEstadoSetUp;
        NeedsToro::setUp as needsToroSetUp;
        NeedsPersonal::setUp as needsPersonalSetUp;
    }

    private $ganadoServicioMonta;


    protected function setUp(): void
    {
        $this->needsSetupRequestSetUp();
        $this->needsEstadoSetUp();
        $this->needsToroSetUp();
        $this->generarGanado();
        $this->needsPersonalSetUp();

        $this->ganadoServicioMonta
            = $this->ganado;
    }


    private function generarPartos(): Collection
    {
        //partos añoa actual
        Parto::factory()
            ->count(10)
            ->for($this->ganadoServicioMonta)
            //se usa el state en lugar de for para asegurarse de que cada parto tenga una cria distinta, con for una misma cria pertenececira a todos los partos
            ->has(PartoCria::factory()->state(['ganado_id' => Ganado::factory()->for($this->hacienda)->hasAttached($this->estado)->create(['fecha_nacimiento' => now()->format('Y-m-d')])]))
            ->for($this->toro, 'partoable')
            ->create(['personal_id' => $this->veterinario]);

        //partos un año anterior al actual
        Parto::factory()
            ->count(10)
            ->for($this->ganadoServicioMonta)
            //se usa el state en lugar de for para asegurarse de que cada parto tenga una cria distinta, con for una misma cria pertenececira a todos los partos
            ->has(PartoCria::factory()->state(['ganado_id' => Ganado::factory()->for($this->hacienda)->hasAttached($this->estado)->create(['fecha_nacimiento' => now()->subYear()->format('Y-m-d')])]))
            ->for($this->toro, 'partoable')
            ->create(['personal_id' => $this->veterinario]);

        //partos dos años anterior al actualk
        return Parto::factory()
            ->count(10)
            ->for($this->ganadoServicioMonta)
            //se usa el state en lugar de for para asegurarse de que cada parto tenga una cria distinta, con for una misma cria pertenececira a todos los partos
            ->has(PartoCria::factory()->state(['ganado_id' => Ganado::factory()->for($this->hacienda)->hasAttached($this->estado)->create(['fecha_nacimiento' => now()->subYear(2)->format('Y-m-d')])]))
            ->for($this->toro, 'partoable')
            ->create(['personal_id' => $this->veterinario]);
    }


    public function test_resumen_natalidad(): void
    {
        $this->generarPartos();
        $response = $this->actingAs($this->user)->withSession(['hacienda_id' => $this->hacienda->id])->getJson(route('resumenesAnual.resumenNatalidad'));
        $response->assertStatus(200);
        $response->assertJson(fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
        $json->has('nacimientos_ultimos_5_año.0', fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
        => $json->whereAllType([
            'año' => 'string',
            'partos_producidos' => 'integer',
            'poblacion' => 'integer',
            'tasa_natalidad' => 'float|integer'
        ]))
            ->has('nacimientos_año_actual', fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
            => $json->whereAllType([
                'año' => 'string',
                'total' => 'integer',
                'machos' => 'integer',
                'hembras' => 'integer',
            ])));
    }
}
