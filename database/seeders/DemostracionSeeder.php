<?php

namespace Database\Seeders;

use App\Models\Comprador;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Estado;
use App\Models\Fallecimiento;
use App\Models\Ganado;
use App\Models\Leche;
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

        $user = User::factory()->create();
        $estado = Estado::all();

        $toro = Toro::factory()
            ->count($elementos)
            ->for($user)
            ->for(Ganado::factory()->for($user)->create(['sexo' => 'M']))->create();

        $veterinario
            = Personal::factory()
            ->for($user)
            ->create(['cargo_id' => 2]);

        Personal::factory()
            ->count($elementos)
            ->for($user)
            ->create();

        $ganado = Ganado::factory()
            ->count($elementos)
            ->hasPeso(1)
            ->sequence(
                ['tipo_id' => 1],
                ['tipo_id' => 2],
                ['tipo_id' => 3],
                ['tipo_id' => 4],

            )
            ->hasEvento(1)
            ->hasAttached($estado)
            ->for($user)
            ->create();

        Revision::factory()
            ->count($elementos)
            ->for($ganado[0])
            ->create(['personal_id' => $veterinario]);

        Servicio::factory()
            ->count(30)
            ->for($ganado[0])
            ->for($toro[0])
            ->create(['personal_id' => $veterinario]);

        Parto::factory()
            ->count(15)
            ->for($ganado[0])
            ->for(Ganado::factory()->for($user)->hasAttached(Estado::firstWhere('estado', 'sano'))->hasPeso(1), 'ganado_cria')
            ->for($toro[0])
            ->create(['personal_id' => $veterinario]);


        Leche::factory()
            ->count($elementos)
            ->for($ganado[0])
            ->for($user)
            ->create();


        VentaLeche::factory()
            ->count($elementos)
            ->for(Precio::factory()->for($user))
            ->for($user)
            ->create();

        Venta::factory()
            ->count($elementos)
            ->for($user)
            ->for(Ganado::factory()->for($user)->hasPeso(1)->hasAttached($estado)->create())
            ->for(Comprador::factory()->for($user)->create())
            ->create();

        Fallecimiento::factory()
            ->count($elementos)
            ->for(Ganado::factory()->for($user)->hasAttached($estado))
            ->create();
    }
}
