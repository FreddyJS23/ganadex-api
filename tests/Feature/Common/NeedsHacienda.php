<?php

namespace Tests\Feature\Common;

use App\Models\Hacienda;
use Illuminate\Database\Eloquent\Collection;

trait NeedsHacienda
{
    use NeedsUser {
        setUp as needsUserSetUp;
        getSessionInitializationArray as needsUserGetSessionInitializationArray;
    }

    private Hacienda $hacienda;
    private Hacienda $otraHacienda;
    private int $cantidad_haciendas = 10;


    protected function setUp(): void
    {
        $this->needsUserSetUp();

        $this->hacienda = Hacienda::factory()->for($this->user)->create(['nombre' => 'hacienda_sesion']);
    }

    protected function getSessionInitializationArray(): array
    {
        return $this->needsUserGetSessionInitializationArray() + [
            'hacienda_id' => $this->hacienda->id
        ];
    }

    protected function crearOtraHacienda():Hacienda
    {
        return $this->otraHacienda = Hacienda::factory()
        ->for($this->user)
        ->create(['nombre' => 'otro_hacienda']);
    }

    private function generarHaciendas(): Collection
    {
        return Hacienda::factory()
            ->count($this->cantidad_haciendas)
            ->for($this->user)
            ->create();
    }
}
