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
     *  @param int $cantidad
     *  @param Estado | null $estado Por defecto asigna todos los estados
     *  @param bool $eventosNull Crear eventos con fechas nulas
     *  @param int | null $tipoId Tipo de ganado
     *  @param bool | null $hembras Crear ganados hembras
     *  @param bool | null $machos Crear ganados machos
     * @return Collection<Ganado>
     */
    private function generarGanados(
        int | null $cantidad = null,
        Estado | null $estado = null,
        $eventosNull = false,
        bool  $eventosLejanos = false,
        int | null $tipoId = null,
        bool | null $hembras = null,
        bool | null $machos = null
    ): Collection {


        $ganados = Ganado::factory()
            ->count($cantidad ?? $this->cantidad_ganado)
            ->hasPeso(1)
            ->hasAttached($estado ?? $this->estado)
            ->hasVacunaciones(3, ['hacienda_id' => $this->hacienda->id]);


        if ($eventosNull) {
            $ganados=$ganados->hasEvento([
                'prox_revision' => null,
                'prox_parto' => null,
                'prox_secado' => null
            ]);
        }elseif ($eventosLejanos) {
            $ganados=$ganados->hasEvento([
                'prox_revision' => now()->addDays(30)->format('Y-m-d'),
                'prox_parto' => now()->addDays(30)->format('Y-m-d'),
                'prox_secado' => now()->addDays(30)->format('Y-m-d'),
            ]);
        }
        else $ganados=$ganados->hasEvento();;


        if ($tipoId) {
            $ganados->state(new Sequence(fn(): array => ['tipo_id' => $tipoId]));
        }

        if ($hembras) {
            $ganados->state(new Sequence(fn(): array => ['sexo' => 'H']));
        }

        if ($machos) {
            $ganados->state(new Sequence(fn(): array => ['sexo' => 'H']));
        }

        return $ganados->for($this->hacienda)->create();
    }

/**
     * Este método genera un objeto Ganado.
     * @param Estado $estado
     * @return Ganado
     */
    private function generarGanado(Estado | null $estado = null): Ganado
    {
        return $this->ganado
        = Ganado::factory()
        ->hasPeso(1)
        ->hasEvento([
            'prox_revision' => null,
            'prox_parto' => null,
            'prox_secado' => null
        ])
        ->hasAttached($estado ??$this->estadoSano)
        ->for($this->hacienda)
        ->create([
            'nombre' => 'test',
            'numero' => 392,
            'origen_id' => 1,
            'sexo' => 'H',]);
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
