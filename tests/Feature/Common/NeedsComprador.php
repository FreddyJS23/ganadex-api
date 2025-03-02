<?php

namespace Tests\Feature\Common;

use App\Models\Comprador;
use Illuminate\Support\Collection;

trait NeedsComprador
{
    use NeedsHacienda;

    private array $comprador = [
        'nombre' => 'test',
    ];

    private int $cantidad_comprador = 10;

    private function generarComprador(): Collection
    {
        return Comprador::factory()
            ->count($this->cantidad_comprador)
            ->for($this->hacienda)
            ->create();
    }
}
