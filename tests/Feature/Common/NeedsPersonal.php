<?php

namespace Tests\Feature\Common;

use App\Models\Personal;
use Illuminate\Database\Eloquent\Collection;

enum cargosPersonal: int{
    case obrero = 1;
    case veterinario = 2;
}

trait NeedsPersonal
{
    private Personal $veterinario;
    private int $cantidad_personal = 10;


    protected function setUp(): void
    {
        $this->veterinario
        = Personal::factory()
            ->for($this->user)->hasAttached($this->hacienda)
            ->create(['cargo_id' => 2]);
    }

    private function generarPersonal(int $cantidad_elementos= 0,CargosPersonal $cargoPersonal=CargosPersonal::obrero): Collection
    {
        return Personal::factory()
            ->count($cantidad_elementos ?? $this->cantidad_personal)
            ->hasAttached($this->hacienda)
            ->for($this->user)
            ->create(['cargo_id' => $cargoPersonal->value]);
    }
}
