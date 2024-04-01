<?php

namespace Tests\Feature;

use App\Models\Ganado;
use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class NotificacionTest extends TestCase
{
    use RefreshDatabase;

    private $cantidad_notificaciones = 10;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user
            = User::factory()->create();
    }
    private function generarNotificaciones(): Collection
    {

        return Notificacion::factory()
            ->count($this->cantidad_notificaciones)
            ->for($this->user)
            ->for(Ganado::factory()->for($this->user)->hasEvento()->create())
            ->create();
    }


    public function test_obtener_notificaciones(): void
    {
        $this->generarNotificaciones();

        $response = $this->actingAs($this->user)->getJson(route('notificaciones.index'));
        $response->assertStatus(200)->assertJson(
            fn (AssertableJson $json) =>
            $json->whereType('notificaciones', 'array')
                ->has('notificaciones', fn (AssertableJson $json) =>
                $json->has(
                    'revision.0',
                    fn (AssertableJson $json)
                    => $json->whereAllType(['id' => 'integer', 'tipo' => 'string', 'leido' => 'boolean', 'dias_para_evento' => 'integer'])
                        ->has(
                            'ganado',
                            fn (AssertableJson $json)
                            => $json->whereAllType(['id' => 'integer', 'numero' => 'integer'])
                        )
                )->has(
                    'secado.0',
                    fn (AssertableJson $json)
                    => $json->whereAllType(['id' => 'integer', 'tipo' => 'string', 'leido' => 'boolean', 'dias_para_evento' => 'integer'])
                        ->has(
                            'ganado',
                            fn (AssertableJson $json)
                            => $json->whereAllType(['id' => 'integer', 'numero' => 'integer'])
                        )
                )->has(
                    'parto.0',
                    fn (AssertableJson $json)
                    => $json->whereAllType(['id' => 'integer', 'tipo' => 'string', 'leido' => 'boolean', 'dias_para_evento' => 'integer'])
                        ->has(
                            'ganado',
                            fn (AssertableJson $json)
                            => $json->whereAllType(['id' => 'integer', 'numero' => 'integer'])
                        )
                ))


        );
    }

    public function test_eliminar_notificacion(): void
    {
        $notificacions = $this->generarNotificaciones();
        $idRandom = rand(0, $this->cantidad_notificaciones - 1);
        $idToDelete = $notificacions[$idRandom]->id;

        $response = $this->actingAs($this->user)->deleteJson(route('notificaciones.destroy', ['notificacion' => $idToDelete]));

        $response->assertStatus(200)->assertJson(['notificacionID' => $idToDelete]);
    }

    public function test_eliminar_todas_notificaciones(): void
    {
        $this->generarNotificaciones();

        //eliminar todas las notificaciones
        $this->actingAs($this->user)->getJson(route('notificaciones.destroyAll'));

        $response = $this->actingAs($this->user)->getJson(route('notificaciones.index'));
        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) => $json->has('notificaciones', 0));
    }
}
