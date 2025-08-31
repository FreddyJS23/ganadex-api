<?php

namespace Tests\Feature\Common;

use App\Models\Personal;

trait NeedsVeterinario
{
    private Personal $veterinario;

    protected function setUp(): void
    {
        $this->veterinario
        = Personal::factory()
            ->for($this->user)->hasAttached($this->hacienda)
            ->create(['cargo_id' => 2]);
    }

}
