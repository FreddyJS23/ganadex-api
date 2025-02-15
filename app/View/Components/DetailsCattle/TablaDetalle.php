<?php

namespace App\View\Components\DetailsCattle;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TablaDetalle extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $tituloDetalle,
        public array $detalles
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.details-cattle.tabla-detalle');
    }
}
