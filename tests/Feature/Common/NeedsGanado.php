<?php

namespace Tests\Feature\Common;

use App\Models\Estado;
use App\Models\Ganado;
use Illuminate\Support\Collection;

trait NeedsGanado
{
    private int $cantidad_ganado = 10;

    /** @var Collection<int, Estado> */
    private Collection $estado;

    /** @var Collection<int, Ganado> */
    private Collection $ganado;

    abstract private function generarGanado(): Collection;
}
