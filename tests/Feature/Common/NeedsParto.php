<?php

namespace Tests\Feature\Common;

use App\Models\Ganado;
use App\Models\Parto;
use App\Models\PartoCria;

use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Collection;

trait NeedsParto
{
    private int $cantidad_parto = 10;

    /**
     * @var Collection<Parto>
     * @param int $cantidad
     * @param bool $efectividad Indica si los partos serán usados para calcular efectividad (true) o no (false)
     */
    private function generarPartos(int $cantidad = 0, bool $efectividad = false): Collection
    {
        $parto =  Parto::factory()
            ->count($cantidad ?? $this->cantidadPartos)
            ->for($this->ganado)
            ->for($this->toro, 'partoable')
            ->has(
                PartoCria::factory()
                    ->state(
                        [
                            'ganado_id' => Ganado::factory()->for($this->hacienda)->hasAttached($this->estado)
                        ]
                    )
            );

        if ($efectividad) {
            $parto
                ->sequence(
                    fn(Sequence $sequence): array =>
                    [
                        'fecha' => now()->subDays(random_int(1, 30))->subMonths(random_int(1, 3))
                    ]
                );
        }
        return $parto->create(['personal_id' => $this->veterinario]);
    }
}
