<?php

namespace Tests\Feature\Common;

use App\Models\Estado;
use App\Models\Ganado;
use App\Models\Leche;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Sequence;
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
     * Este método genera una colección de objetos Ganado con un registro de pesaje de leche y diferentes tipos de ganado.
     * @return Collection<Ganado>
     */
    private function generarGanadoConPesajesLeche(): Collection
    {

        return Ganado::factory()
            ->count($this->cantidad_elementos)
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            ->has(
                Leche::factory()->for($this->hacienda)->state(
                    fn(array $attributes, Ganado $ganado): array => [
                        'ganado_id' => $ganado->id,
                        'fecha' => Carbon::now()->format('Y-m-d')
                    ]
                ),
                'pesajes_leche'
            )
            ->state(new Sequence(fn(): array => ['tipo_id' => random_int(1, 4)]))
            ->for($this->hacienda)
            ->create();
    }

    /**
     * Genera una fecha aleatoria dentro de un año específico para el pesaje anual de leche.
     * @param int $año Año para el cual se genera la fecha.
     * @return string Fecha generada en formato 'Y-m-d'.
     */
    private function mesesPesajeAnual(int $año): string
    {
        $mes = random_int(0, 11);
        $fechaInicial = Carbon::create($año, 1, 20);
        $fechaConMesAñadido = $fechaInicial->addMonths($mes)->format('Y-m-d');

        return $mes == 0 ? $fechaInicial->format('Y-m-d') : $fechaConMesAñadido;
    }
    /**
     * Este método genera una colección de objetos Ganados con registros de pesajes de leche para proyecciones anuales.
     * @param int $año año para el cual se generan los registros de pesaje de leche.
     * @return Collection<Ganado>
     */
    private function generarGanadoPesajeLecheAnual(int $año): Collection
    {
        return Ganado::factory()
            ->count($this->cantidad_elementos)
            ->hasPeso(1)
            ->hasEvento(1)
            ->hasAttached($this->estado)
            /* habra veces que se repita una fecha, por ende se crea 50 elementos ganado,
            con 12 elementos de produccion lactea que serian la cantidad de meses que existen,
            asi siempre todos los meses estaran cubiertos por lo menos una vez */
            ->has(
                Leche::factory()
                    ->for($this->hacienda)
                    ->count(12)
                    ->state(
                        fn(array $attributes, Ganado $ganado): array => [
                            'ganado_id' => $ganado->id
                        ]
                    )
                    ->sequence(fn(): array => [
                        'fecha' => $this->mesesPesajeAnual($año)
                    ]),
                'pesajes_leche'
            )
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
            ->hasEvento(1, ['prox_revision' => null])
            ->hasAttached($estado)
            ->for($this->hacienda)
            ->create();
    }
}
