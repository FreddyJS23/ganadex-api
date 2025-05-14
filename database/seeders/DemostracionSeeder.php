<?php

namespace Database\Seeders;

use App\Models\Comprador;
use App\Models\Configuracion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Estado;
use App\Models\Fallecimiento;
use App\Models\Hacienda;
use App\Models\Ganado;
use App\Models\GanadoDescarte;
use App\Models\Insumo;
use App\Models\Plan_sanitario;
use App\Models\Leche;
use App\Models\Notificacion;
use App\Models\PajuelaToro;
use App\Models\Parto;
use App\Models\PartoCria;
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

        $hacienda
        = Hacienda::factory()
        ->for($user)
        ->create();


        $crearUsuarioVeterinario = UsuarioVeterinario::factory()
        ->for(Personal::factory()->hasAttached($hacienda)->for($user)->create(['cargo_id' => 2]), 'veterinario')
        ->create(['admin_id' => $user->id]);

        $userVeterinario = User::find($crearUsuarioVeterinario->user_id);

        $userVeterinario->assignRole('veterinario');

        $hacienda->veterinarios()->attach($crearUsuarioVeterinario->veterinario);

        $estadoSano = Estado::firstWhere('estado', 'sano');
        $estadoFallecido = Estado::firstWhere('estado', 'fallecido');
        $estadoGestacion = Estado::firstWhere('estado', 'gestacion');
        $estadoVendido = Estado::firstWhere('estado', 'vendido');
        $estadoLactancia = Estado::firstWhere('estado', 'lactancia');

        $toroParaServicio = Ganado::factory()
        ->for($hacienda)
        ->hasPeso(1)
        ->has(Toro::factory()->for($hacienda))
        ->hasAttached($estadoSano)
        ->create(['sexo' => 'M', 'tipo_id' => 4]);

        $toros = Ganado::factory()
            ->count($elementos)
            ->for($hacienda)
            ->hasPeso(1)
            ->sequence(
                ['tipo_id' => 1],
                ['tipo_id' => 2],
                ['tipo_id' => 3],
                ['tipo_id' => 4],
            )
            ->has(Toro::factory()->for($hacienda))
            ->hasAttached($estadoSano)
            ->create(['sexo' => 'M']);

        $toros = Toro::all();


        $pajuelaToros = PajuelaToro::factory()
            ->count($elementos)
            ->for($hacienda)
            ->create();

        //Ganado descarte
        Ganado::factory()
            ->count($elementos)
            ->for($hacienda)
            ->hasPeso(1)
            ->has(GanadoDescarte::factory()->for($hacienda))
            ->hasAttached($estadoSano)
            ->create(['sexo' => array_rand(['M' => 'M', 'H' => 'H'])]);

        $veterinario
            = Personal::factory()
                ->for($user)
            ->hasAttached($hacienda)
            ->create(['cargo_id' => 2]);

        Personal::factory()
            ->for($user)
            ->count($elementos)
            ->hasAttached($hacienda)
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
            ->for($hacienda)
            ->create();

        //vacas con revisiones
            Ganado::factory()
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
            ->for($hacienda)
            ->create();

        //vacas con servicios
            Ganado::factory()
            ->count($cantidadGanados)
            ->hasPeso(1)
            ->hasServicios(5, ['servicioable_id' => $toroParaServicio->toro->id,'servicioable_type' => $toroParaServicio->toro->getMorphClass(), 'personal_id' => $veterinario->id])
            ->hasEvento(['prox_revision' => null, 'prox_parto' => null, 'prox_secado' => null])
            ->hasAttached($estadoSano)
            ->for($hacienda)
            ->create(['tipo_id' => 3,]);

        //vacas con revision preñada
            Ganado::factory()
            ->count($cantidadGanados)
            ->hasPeso(1)
            ->hasRevision(5, ['personal_id' => $veterinario, 'tipo_revision_id' => 1])
            ->hasServicios(5, ['servicioable_id' => $toroParaServicio->toro->id,'servicioable_type' => $toroParaServicio->toro->getMorphClass(), 'personal_id' => $veterinario->id])
            ->hasEvento(['prox_revision' => null])
            ->hasAttached([$estadoSano, $estadoGestacion])
            ->for($hacienda)
            ->create(['tipo_id' => 3]);


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
                     //se usa el state en lugar de for para asegurarse de que cada parto tenga una cria distinta, con for una misma cria pertenececira a todos los partos
                    ->has(PartoCria::factory()
                        ->state(['ganado_id'
                                =>Ganado::factory()
                                ->for($hacienda)->hasPeso(1)
                                ->hasAttached($estadoSano)
                                ->hasEvento(['prox_revision' => null, 'prox_parto' => null, 'prox_secado' => null])
                            ]
                        )
                    )
                    //alternar un parto con monta y otro con inseminacion
                    ->for($i % 2 == 0 ? $toros[rand(0, $elementos - 1)] : $pajuelaToros[rand(0, $elementos - 1)], 'partoable')
                    ->create(['personal_id' => $veterinario]);
            }

              //sincronizar estado sano y lactancia
              $ganados[$i]->estados()->sync([$estadoSano->id,$estadoLactancia->id]);


            Leche::factory()
                ->count(5)
                ->for($ganados[$i])
                ->for($hacienda)
                ->create();
        }


       /*  VentaLeche::factory()
            ->count($elementos)
            ->for(Precio::factory()->for($hacienda))
            ->for($hacienda)
            ->create(); */

        Venta::factory()
            ->count($elementos)
            ->for($hacienda)
            ->state(['ganado_id'=>Ganado::factory()->for($hacienda)->hasPeso(1)->hasAttached($estadoVendido), 'comprador_id'=>Comprador::factory()->for($hacienda)])
            ->create();

        Fallecimiento::factory()
            ->count($elementos)
            ->state(['ganado_id'=>Ganado::factory()->for($hacienda)->hasAttached($estadoFallecido)])
            ->create();

       /*  Insumo::factory()
            ->count($elementos)
            ->for($hacienda)
            ->create(); */

        Plan_sanitario::factory()
            ->count($elementos)
            ->for($hacienda)
            ->create();
    }
}
