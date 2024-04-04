<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TablasVacasProductoras extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $titulo1,
        public array $topVacasProductoras,
        public string $titulo2,
        public array $topVacasMenosProductoras,
    )
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.tablas-vacas-productoras');
    }
}
