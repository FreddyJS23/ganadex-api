<?php

namespace Tests\Feature\Common;

use App\Models\Comprador;
use App\Models\Ganado;
use App\Models\Venta;
use Illuminate\Support\Collection;

trait NeedsVentaGanado
{
    private int $cantidad_ventas = 10;

    /** @var Collection<Venta> */
    private Collection $ventas;


    private function generarVentas(): Collection
    {
        $compradores = Comprador::factory()
            ->for($this->hacienda)
            ->count(5)
            ->create();

        return Venta::factory()
            ->count($this->cantidad_ventas)
            ->for($this->hacienda)
            ->for(Ganado::factory()
                ->for($this->hacienda)
                ->hasPeso(1)
                ->hasAttached($this->estado)->create())
            ->sequence(
                ['comprador_id' => $compradores->random()->id],
                ['comprador_id' => $compradores->random()->id],
                ['comprador_id' => $compradores->random()->id],
            )
            ->create();
    }


}
