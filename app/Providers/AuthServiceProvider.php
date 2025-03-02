<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use App\Models\User;
use App\Models\UsuarioVeterinario;
use App\Policies\ConfiguracionPolicy;
use App\Policies\HaciendaPolicy;
use App\Policies\LogsVeterinarioPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::define('verificar_sesion_hacienda', [HaciendaPolicy::class,'verificar_sesion_hacienda']);
        Gate::define('crear_sesion_hacienda', [HaciendaPolicy::class,'crear_sesion_hacienda']);
        Gate::define('update', [ConfiguracionPolicy::class,'update']);

        Gate::define(
            'view-logs',
            fn(User $user, UsuarioVeterinario $usuarioVeterinario) => $user->hasRole('admin') &&  $user->id === $usuarioVeterinario->admin_id
        );
    }
}
