<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            EstadoSeeder::class,
           // UserSeeder::class,
           GanadoTipoSeeder::class,
           CargoSeeder::class,
           TiposNotificacionSeeder::class,
           VacunaSeeder::class,
           TipoRevisionSeeder::class,
           PreguntasSeguridadSeeder::class,
           OrigenGanadoSeeder::class,
           //DemostracionSeeder::class,
        ]);
    }
}
