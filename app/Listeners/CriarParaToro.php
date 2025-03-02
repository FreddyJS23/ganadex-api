<?php

namespace App\Listeners;

use App\Events\PartoHechoCriaToro;
use App\Models\Toro;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CriarParaToro
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PartoHechoCriaToro $event): void
    {
        $toro=new Toro();
        $toro->hacienda_id = session('hacienda_id');
        $toro->ganado()->associate($event->parto->ganado_cria)->save();
    }
}
