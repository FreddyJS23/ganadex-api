<?php

namespace Database\Seeders;

use App\Models\Comprador;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Estado;
use App\Models\Fallecimiento;
use App\Models\Ganado;
use App\Models\GanadoDescarte;
use App\Models\Insumo;
use App\Models\Leche;
use App\Models\Notificacion;
use App\Models\Parto;
use App\Models\Personal;
use App\Models\Precio;
use App\Models\Revision;
use App\Models\Servicio;
use App\Models\Toro;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaLeche;

class DemostracionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $elementos = 30;
        $cantidadGanados = 10;

        $user = User::factory()->create();
        $estadoSano = Estado::firstWhere('estado', 'sano');
        $estadoFallecido = Estado::firstWhere('estado', 'fallecido');
        $estadoVendido = Estado::firstWhere('estado', 'vendido');

        $toros = Toro::factory()
            ->count($elementos)
            ->for($user)
            ->for(Ganado::factory()->for($user)->create(['sexo' => 'M']))->create();
        
        GanadoDescarte::factory()
            ->count($elementos)
            ->for($user)
            ->for(Ganado::factory()->for($user)->create(['sexo' => array_rand(['M', 'F'])]))->create();

        $veterinario
            = Personal::factory()
            ->for($user)
            ->create(['cargo_id' => 2]);

        Personal::factory()
            ->count($elementos)
            ->for($user)
            ->create();

        $ganados = Ganado::factory()
            ->count($cantidadGanados)
            ->hasPeso(1)
            ->sequence(
                ['tipo_id' => 1],
                ['tipo_id' => 2],
                ['tipo_id' => 3],
                ['tipo_id' => 4],

            )
            ->hasEvento(1)
            ->hasAttached($estadoSano)
            ->for($user)
            ->create();

        for ($i = 0; $i < $cantidadGanados; $i++) {
            $numeroServicios=15;
            $numeroPartos=rand(1,$numeroServicios);
            Revision::factory()
                ->count(5)
                ->for($ganados[$i])
                ->create(['personal_id' => $veterinario]);

            Servicio::factory()
                ->count(5)
                ->for($ganados[$i])
                ->for($toros[0])
                ->create(['personal_id' => $veterinario]);

            Parto::factory()
                ->count($numeroPartos)
                ->for($ganados[$i])
                ->for(Ganado::factory()->for($user)->hasAttached($estadoSano)->hasPeso(1), 'ganado_cria')
                ->for($toros[0])
                ->create(['personal_id' => $veterinario]);


            Leche::factory()
                ->count(5)
                ->for($ganados[$i])
                ->for($user)
                ->create();
}

       


        VentaLeche::factory()
            ->count($elementos)
            ->for(Precio::factory()->for($user))
            ->for($user)
            ->create();

        Venta::factory()
            ->count($elementos)
            ->for($user)
            ->for(Ganado::factory()->for($user)->hasPeso(1)->hasAttached($estadoVendido)->create())
            ->for(Comprador::factory()->for($user)->create())
            ->create();

        Fallecimiento::factory()
            ->count($elementos)
            ->for(Ganado::factory()->for($user)->hasAttached($estadoFallecido))
            ->create();


         Notificacion::factory()
            ->count($elementos)
            ->for($user)
            ->for(Ganado::factory()->for($user)->hasEvento()->create())
            ->create();

        Insumo::factory()
        ->count($elementos)
        ->for($user)
        ->create();
    }
}
