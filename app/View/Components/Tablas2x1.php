<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Tablas2x1 extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $titulo1,
        /**
         * @var array{string:string}
         */
        public array | string $detalles1,
        public string $titulo2,
        /**
         * @var array{string:string}
         */
        public array | string $detalles2,
    ) {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.tablas2x1');
    }
}
