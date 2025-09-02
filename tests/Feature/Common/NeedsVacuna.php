<?php

namespace Tests\Feature\Common;

use App\Models\Vacuna;
use Illuminate\Support\Collection;

trait NeedsVacuna
{
    private int $cantidad_vacunas = 10;

    /** @var Collection<Vacuna> */
    private Collection $vacunas;


    private function generarVacunas(): Collection
    {
        return Vacuna::factory()
            ->count($this->cantidad_vacunas)
            ->create();
    }
}
