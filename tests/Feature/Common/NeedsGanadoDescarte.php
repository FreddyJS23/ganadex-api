<?php

namespace Tests\Feature\Common;

use App\Models\Ganado;
use App\Models\GanadoDescarte;
use Illuminate\Support\Collection;

trait NeedsGanadoDescarte
{
    private int $cantidad_ganadoDescarte = 10;

    /** @var Collection<GanadoDescarte> */
    private Collection $ganadoDescartes;

    private GanadoDescarte $ganado_descarte;


    private function generarGanadoDescartes(): Collection
    {
        return $this->ganadoDescartes = GanadoDescarte::factory()
            ->count(10)
            ->for($this->hacienda)
            ->state(
                ['ganado_id' => Ganado::factory()->hasPeso()->hasAttached($this->estado)->hasVacunaciones(
                    3,
                    ['hacienda_id' => $this->hacienda->id]
                )->state(['hacienda_id' => $this->hacienda->id, 'sexo' => 'M', 'tipo_id' => 4])]
            )
            ->create();
    }

    private function generarGanadoDescarte(): GanadoDescarte
    {
        return  $this->ganado_descarte
            = GanadoDescarte::factory()
            ->for($this->hacienda)
            ->for(Ganado::factory()
                ->hasPeso()
                ->for($this->hacienda)
                ->hasAttached($this->estado)
                ->create(['nombre' => 'descarte', 'numero' => 350]))
            ->create();
    }
}
