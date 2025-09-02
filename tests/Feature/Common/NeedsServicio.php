<?php

namespace Tests\Feature\Common;

use App\Models\Servicio;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Collection;

trait NeedsServicio
{
    private int $cantidad_servicio = 10;

/**
 * @var Collection<Servicio>
 * @param int $cantidad
 * @param bool $efectividad Indica si los servicios serán usados para calcular efectividad (true) o no (false)
  */
    private function generarServicios(int $cantidad = 0, bool $efectividad = false): Collection
    {
        $servicio =  Servicio::factory()
            ->count($cantidad ?? $this->cantidadServicios)
            ->for($this->ganado)
            ->for($this->toro, 'servicioable');

        if ($efectividad) {
            $servicio
                ->sequence(
                    fn(Sequence $sequence): array =>
                    [
                        'fecha' => now()->subDays(random_int(1, 30))->subMonths(random_int(1, 3))
                    ]
                );
        }
        return $servicio->create(['personal_id' => $this->veterinario]);
    }
}
