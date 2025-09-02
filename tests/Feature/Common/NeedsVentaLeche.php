<?php

namespace Tests\Feature\Common;

use App\Models\Precio;
use App\Models\Venta;
use App\Models\VentaLeche;
use Illuminate\Support\Collection;

trait NeedsVentaLeche
{
    private int $cantidad_ventaLeche = 100;

    /** @var Collection<Venta> */
    private Collection $ventas;


    private function generarVentaLeche(): Collection
    {
        return VentaLeche::factory()
            ->count($this->cantidad_ventaLeche)
            ->for(Precio::factory()->for($this->hacienda))
            ->for($this->hacienda)
            ->create();
    }

}
