<?php

namespace Tests\Feature\Common;

use App\Models\Estado;
use App\Models\Ganado;
use Illuminate\Support\Collection;

trait NeedsGanado
{
    private int $cantidad_ganado = 10;

    /** @var Collection<Ganado> */
    private Collection $ganados;

    private Ganado  $ganado;

    /**
     * Este método genera una colección de objetos Ganado con un tamaño de $this->cantidad_ganado
     * @return Collection<Ganado>
     */
    private function generarGanados(): Collection
    {
        return Ganado::factory()
            ->count($this->cantidad_ganado)
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->hasVacunaciones(3, ['hacienda_id' => $this->hacienda->id])
            ->for($this->hacienda)
            ->create();
    }

    /**
     * Este método genera un objeto Ganado
     * @return Ganado
     */
    private function generadoGanado(): Ganado
    {
        return  $this->ganado
            = Ganado::factory()
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->for($this->hacienda)
            ->create();
    }

    /**
     * Este método genera una colección de 3 objetos Ganado con 5 revisiones cada uno, asociadas a un veterinario específico.
     * @param Estado $estado
     * @return Collection<Ganado>
     */
    private function generarGanadoConRevision(Estado $estado): Collection
    {
        return  Ganado::factory()
        ->count(3)
        ->hasPeso(1)
        ->hasRevision(5, ['personal_id' => $this->veterinario->id])
        ->hasEvento(1)
        ->hasAttached($estado)
        ->for($this->hacienda)
        ->create();
    }

    /**
     * Este método genera un objeto Ganado sin una revisión pendiente.
     * @param Estado $estado
     * @return Collection<Ganado>
     */
    private function generarGanadoSinRevisionPendiente(Estado $estado): Collection
    {
        return $this->ganado
        = Ganado::factory()
        ->hasPeso(1)
        ->hasEvento(1,['prox_revision'=>null])
        ->hasAttached($estado)
        ->for($this->hacienda)
        ->create();
    }
}
