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

    private GanadoDescarte $ganadoDescarte;


    private function generarGanadoDescartes(): Collection
    {
        return $this->ganadoDescartes = GanadoDescarte::factory()
            ->count(10)
            ->for($this->hacienda)
            ->state(
                ['ganado_id' => Ganado::factory()->hasAttached($this->estado)->hasVacunaciones(
                    3,
                    ['hacienda_id' => $this->hacienda->id]
                )->state(['hacienda_id' => $this->hacienda->id, 'sexo' => 'M', 'tipo_id' => 4])]
            )
            ->create();
    }
}
