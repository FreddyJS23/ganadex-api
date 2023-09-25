<?php

namespace App\Providers;

use App\Events\NaceMacho;
use App\Events\PartoHecho;
use App\Events\RevisionPrenada;
use App\Events\ServicioHecho;
use App\Listeners\CaparBecerro;
use App\Listeners\EstadoGestacion;
use App\Listeners\RevisionPosParto;
use App\Listeners\RevisionServicio;
use App\Listeners\Secado;
use App\Listeners\EstadoPosParto;
use App\Listeners\CambiaAvaca;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;




class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        ServicioHecho::class => [RevisionServicio::class,],
        RevisionPrenada::class => [EstadoGestacion::class, Secado::class,],
        PartoHecho::class => [RevisionPosParto::class,EstadoPosParto::class,CambiaAVaca::class],
        NaceMacho::class => [CaparBecerro::class,],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
