<?php

namespace Tests\Feature\Common;

use App\Models\Estado;
use Illuminate\Database\Eloquent\Collection;

trait NeedsEstado
{
    /** @var Collection<int, Estado> */
    private Collection $estado;

    protected function setUp(): void
    {
        $this->estado = Estado::all();
    }
}
