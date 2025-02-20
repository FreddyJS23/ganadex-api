<?php

namespace Database\Seeders;

use App\Models\Comprador;
use App\Models\Configuracion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Estado;
use App\Models\Fallecimiento;
use App\Models\Finca;
use App\Models\Ganado;
use App\Models\GanadoDescarte;
use App\Models\Insumo;
use App\Models\Jornada_vacunacion;
use App\Models\Leche;
use App\Models\Notificacion;
use App\Models\PajuelaToro;
use App\Models\Parto;
use App\Models\Personal;
use App\Models\Precio;
use App\Models\Revision;
use App\Models\Servicio;
use App\Models\Toro;
use App\Models\User;
use App\Models\UsuarioVeterinario;
use App\Models\Venta;
use App\Models\VentaLeche;
use Illuminate\Support\Facades\Hash;

class DemostracionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $elementos = 30;
        $cantidadGanados = 10;

        $user = User::factory()
        ->create();

        $user->assignRole('admin');

        Configuracion::factory()->for($user)->create();

        $finca
        = Finca::factory()
        ->for($user)
        ->create();


        $crearUsuarioVeterinario = UsuarioVeterinario::factory()
        ->for(Personal::factory()->for($finca)->create(['cargo_id' => 2]), 'veterinario')
        ->create(['admin_id' => $user->id]);

        $userVeterinario = User::find($crearUsuarioVeterinario->user_id)->first();

        $userVeterinario->assignRole('veterinario');


        $estadoSano = Estado::firstWhere('estado', 'sano');
        $estadoFallecido = Estado::firstWhere('estado', 'fallecido');
        $estadoGestacion = Estado::firstWhere('estado', 'gestacion');
        $estadoVendido = Estado::firstWhere('estado', 'vendido');
        $estadoLactancia = Estado::firstWhere('estado', 'lactancia');

        $toroParaServicio = Ganado::factory()
        ->for($finca)
        ->hasPeso(1)
        ->has(Toro::factory()->for($finca))
        ->hasAttached($estadoSano)
        ->create(['sexo' => 'M', 'tipo_id' => 4]);

        $toros = Ganado::factory()
            ->count($elementos)
            ->for($finca)
            ->hasPeso(1)
            ->sequence(
                ['tipo_id' => 1],
                ['tipo_id' => 2],
                ['tipo_id' => 3],
                ['tipo_id' => 4],
            )
            ->has(Toro::factory()->for($finca))
            ->hasAttached($estadoSano)
            ->create(['sexo' => 'M']);

        $toros = Toro::all();


        $pajuelaToros = PajuelaToro::factory()
            ->count($elementos)
            ->for($finca)
            ->create();

        //Ganado descarte
        Ganado::factory()
            ->count($elementos)
            ->for($finca)
            ->hasPeso(1)
            ->has(GanadoDescarte::factory()->for($finca))
            ->hasAttached($estadoSano)
            ->create(['sexo' => array_rand(['M' => 'M', 'H' => 'H'])]);

        $veterinario
            = Personal::factory()
            ->for($finca)
            ->create(['cargo_id' => 2]);

        Personal::factory()
            ->count($elementos)
            ->for($finca)
            ->create();

        //vacas
        $ganados = Ganado::factory()
            ->count($cantidadGanados)
            ->hasPeso(1)
            ->sequence(
                ['tipo_id' => 1],
                ['tipo_id' => 2],
                ['tipo_id' => 3],
                ['tipo_id' => 4],
            )
            ->hasEvento(['prox_revision' => null, 'prox_parto' => null, 'prox_secado' => null])
            ->hasAttached($estadoSano)
            ->for($finca)
            ->create();

        //vacas con revisiones
        $ganados = Ganado::factory()
            ->count($cantidadGanados)
            ->hasPeso(1)
            ->hasRevision(5, ['personal_id' => $veterinario])
            ->sequence(
                ['tipo_id' => 1],
                ['tipo_id' => 2],
                ['tipo_id' => 3],
                ['tipo_id' => 4],
            )
            ->hasEvento(['prox_revision' => null, 'prox_parto' => null, 'prox_secado' => null])
            ->hasAttached($estadoSano)
            ->for($finca)
            ->create();

        //vacas con servicios
        $ganados = Ganado::factory()
            ->count($cantidadGanados)
            ->hasPeso(1)
            ->hasServicios(5, ['servicioable_id' => $toroParaServicio->toro->id,'servicioable_type' => $toroParaServicio->toro->getMorphClass(), 'personal_id' => $veterinario->id])
            ->hasEvento(['prox_revision' => null, 'prox_parto' => null, 'prox_secado' => null])
            ->hasAttached($estadoSano)
            ->for($finca)
            ->create(['tipo_id' => 3, 'sexo' => 'H']);

        //vacas con revision preñada
        $ganados = Ganado::factory()
            ->count($cantidadGanados)
            ->hasPeso(1)
            ->hasRevision(5, ['personal_id' => $veterinario, 'tipo_revision_id' => 1])
            ->hasServicios(5, ['servicioable_id' => $toroParaServicio->toro->id,'servicioable_type' => $toroParaServicio->toro->getMorphClass(), 'personal_id' => $veterinario->id])
            ->hasEvento(['prox_revision' => null])
            ->hasAttached([$estadoSano, $estadoGestacion])
            ->for($finca)
            ->create(['tipo_id' => 3, 'sexo' => 'H']);


        //vacas que ya cumplieron ciclo revision->servicio->parto
        for ($i = 0; $i < $cantidadGanados; $i++) {
            $numeroServicios = 15;
            $numeroPartos = rand(1, $numeroServicios);

            Revision::factory()
                ->count(5)
                ->for($ganados[$i])
                ->create(['personal_id' => $veterinario]);

            //Producir numero de partos y servicios individuales
            for ($j = 0; $j < $numeroPartos; $j++) {
                Servicio::factory()
                    ->count(rand($numeroPartos, $numeroServicios))
                    ->for($ganados[$i])
                    //alternar un servicio con monta y otro con inseminacion
                    ->for($i % 2 == 0 ? $toros[rand(0, $elementos - 1)] : $pajuelaToros[rand(0, $elementos - 1)], 'servicioable')
                    ->create(['personal_id' => $veterinario,'tipo' => $i % 2 == 0 ? 'monta' : 'inseminacion']);

                Parto::factory()
                    ->for($ganados[$i])
                    ->for(Ganado::factory()->for($finca)->hasAttached([$estadoSano,$estadoLactancia])->hasPeso(1), 'ganado_cria')
                    //alternar un parto con monta y otro con inseminacion
                    ->for($i % 2 == 0 ? $toros[rand(0, $elementos - 1)] : $pajuelaToros[rand(0, $elementos - 1)], 'partoable')
                    ->create(['personal_id' => $veterinario]);
            }

            Leche::factory()
                ->count(5)
                ->for($ganados[$i])
                ->for($finca)
                ->create();
        }


       /*  VentaLeche::factory()
            ->count($elementos)
            ->for(Precio::factory()->for($finca))
            ->for($finca)
            ->create(); */

        Venta::factory()
            ->count($elementos)
            ->for($finca)
            ->for(Ganado::factory()->for($finca)->hasPeso(1)->hasAttached($estadoVendido))
            ->for(Comprador::factory()->for($finca))
            ->create();

        Fallecimiento::factory()
            ->count($elementos)
            ->for(Ganado::factory()->for($finca)->hasAttached($estadoFallecido))
            ->create();

       /*  Insumo::factory()
            ->count($elementos)
            ->for($finca)
            ->create(); */

        Jornada_vacunacion::factory()
            ->count($elementos)
            ->for($finca)
            ->create();
    }
}
