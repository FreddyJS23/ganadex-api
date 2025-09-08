<?php

namespace Tests\Feature;

use App\Models\Hacienda;
use App\Models\Ganado;
use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\Feature\Common\NeedsSetupRequest;
use Tests\TestCase;

class NotificacionTest extends TestCase
{
    use NeedsSetupRequest {
        NeedsSetupRequest::setUp as needsSetupRequestSetUp;
    }

    private int $cantidad_notificaciones = 10;


    protected function setUp(): void
    {
        $this->needsSetupRequestSetUp();
    }
    private function generarNotificaciones(): Collection
    {

        return Notificacion::factory()
            ->count($this->cantidad_notificaciones)
            ->for($this->hacienda)
            ->for(Ganado::factory()->for($this->hacienda)->hasEvento()->create())
            ->create();
    }


    public function test_obtener_notificaciones(): void
    {
        $this->generarNotificaciones();

        $response = $this->setUpRequest()->getJson(route('notificaciones.index'));
        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
            $json->whereType('notificaciones', 'array')
                ->has('notificaciones', fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson =>
                $json->has(
                    'revision.0',
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                    => $json->whereAllType(['id' => 'integer', 'tipo' => 'string', 'leido' => 'boolean', 'dias_para_evento' => 'integer'])
                        ->has(
                            'ganado',
                            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                            => $json->whereAllType(['id' => 'integer', 'numero' => 'integer|null'])
                        )
                )->has(
                    'secado.0',
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                    => $json->whereAllType(['id' => 'integer', 'tipo' => 'string', 'leido' => 'boolean', 'dias_para_evento' => 'integer'])
                        ->has(
                            'ganado',
                            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                            => $json->whereAllType(['id' => 'integer', 'numero' => 'integer|null'])
                        )
                )->has(
                    'parto.0',
                    fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                    => $json->whereAllType(['id' => 'integer', 'tipo' => 'string', 'leido' => 'boolean', 'dias_para_evento' => 'integer'])
                        ->has(
                            'ganado',
                            fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson
                            => $json->whereAllType(['id' => 'integer', 'numero' => 'integer|null'])
                        )
                ))
        );
    }

    public function test_eliminar_notificacion(): void
    {
        $notificacions = $this->generarNotificaciones();
        $idRandom = random_int(0, $this->cantidad_notificaciones - 1);
        $idToDelete = $notificacions[$idRandom]->id;

        $response = $this->setUpRequest()->deleteJson(route('notificaciones.destroy', ['notificacion' => $idToDelete]));

        $response->assertStatus(200)->assertJson(['notificacionID' => $idToDelete]);
    }

    public function test_eliminar_todas_notificaciones(): void
    {
        $this->generarNotificaciones();

        //eliminar todas las notificaciones
        $this->setUpRequest()->getJson(route('notificaciones.destroyAll'));

        $response = $this->setUpRequest()->getJson(route('notificaciones.index'));
        $response->assertStatus(200)->assertJson(fn(AssertableJson $json): \Illuminate\Testing\Fluent\AssertableJson => $json->has('notificaciones', 0));
    }
}
