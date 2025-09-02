<?php

namespace Tests\Feature\Common;

use App\Models\Ganado;
use App\Models\Leche;
use Illuminate\Support\Collection;

trait NeedsLeche
{
    private int $cantidad_leche = 10;

    /** @var Collection<Leche> */
    private Collection $leches;


    private function generarLeche(): Collection
    {
        return Leche::factory()
            ->count(10)
            ->for(
                Ganado::factory()
                    ->for($this->hacienda)
                    ->hasPeso(1)
                    ->hasAttached($this->estado)
                    ->create()
            )
            ->for($this->hacienda)
            ->create();
    }
}
