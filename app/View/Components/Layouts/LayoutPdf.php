<?php

namespace App\View\Components\Layouts;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LayoutPdf extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $tituloReporte
    )
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.layouts.layout-pdf');
    }
}
