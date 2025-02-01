<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use App\Policies\ConfiguracionPolicy;
use App\Policies\FincaPolicy;
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
        Gate::define('verificar_sesion_finca',[FincaPolicy::class,'verificar_sesion_finca']);
        Gate::define('crear_sesion_finca',[FincaPolicy::class,'crear_sesion_finca']);
        Gate::define('update',[ConfiguracionPolicy::class,'update']);
    }
}
