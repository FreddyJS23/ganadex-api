<?php

namespace Tests\Feature\Common;

use App\Models\Estado;
use App\Models\Ganado;
use App\Models\Toro;
use Illuminate\Support\Collection;

trait NeedsToro
{
    private int $cantidad_toro = 10;

    /** @var Collection<Toro> */
    private Collection $toros;

    private Toro $toro;

    protected function setUp(): void
    {
        $ganadoFactory=Ganado::factory(['hacienda_id' => $this->hacienda->id, 'sexo' => 'M', 'tipo_id' => 4])
        ->hasVacunaciones(3, ['hacienda_id' => $this->hacienda->id]);

        $this->toro = Toro::factory()
        ->for($this->hacienda)
        ->state(['ganado_id'=>$ganadoFactory])
        ->create();
    }

    private function generarToros(): Collection
    {
        $ganadoFactory=Ganado::factory(['hacienda_id' => $this->hacienda->id, 'sexo' => 'M', 'tipo_id' => 4])
        ->hasVacunaciones(3, ['hacienda_id' => $this->hacienda->id]);

        //usar state para asegurarse de que cada toro tiene una ganado distinta
        return Toro::factory()
            ->count(10)
            ->for($this->hacienda)
            ->state(['ganado_id'=>$ganadoFactory])
            ->create();
    }

}
