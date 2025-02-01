<?php

namespace App\Providers;

use App\Events\CrearSesionFinca;
use App\Events\NaceMacho;
use App\Events\PartoHecho;
use App\Events\RevisionPrenada;
use App\Events\ServicioHecho;
use App\Events\VentaGanado;
use App\Events\FallecimientoGanado;
use App\Events\PesajeLecheHecho;
use App\Events\RevisionDescarte;
use App\Listeners\CaparBecerro;
use App\Listeners\EstadoPosVenta;
use App\Listeners\EstadoGestacion;
use App\Listeners\RevisionPosParto;
use App\Listeners\RevisionServicio;
use App\Listeners\Secado;
use App\Listeners\EstadoPosParto;
use App\Listeners\EstadoPosFallecimiento;
use App\Listeners\CambiaAvaca;
use App\Listeners\VerificarEdadGanado;
use App\Listeners\VerificarPesajeMensualLeche;
use App\Listeners\EstadoPosPesajeMensualLeche;
use App\Listeners\GenerarNotificaciones;
use App\Listeners\DescartarGanado;
use App\Listeners\VerificarVacasAptaParaServicio;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Login;
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
        RevisionDescarte::class => [DescartarGanado::class],
        PartoHecho::class => [RevisionPosParto::class,EstadoPosParto::class,CambiaAVaca::class],
        NaceMacho::class => [CaparBecerro::class,],
        //Login::class=>[],
        CrearSesionFinca::class=>[VerificarEdadGanado::class,VerificarPesajeMensualLeche::class,GenerarNotificaciones::class,VerificarVacasAptaParaServicio::class],
        PesajeLecheHecho::class=>[EstadoPosPesajeMensualLeche::class],
        VentaGanado::class=>[EstadoPosVenta::class],
        FallecimientoGanado::class=>[EstadoPosFallecimiento::class],
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
