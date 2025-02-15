<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TablaCausasFallecimientos extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public array $causasFallecimientos
    ) {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.tabla-causas-fallecimientos');
    }
}
