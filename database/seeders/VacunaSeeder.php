<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VacunaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //tipo de animal 0, representa a que es aplicable a todo el rebaño
        //intervalo de dosis esta representado en días

        DB::table('vacunas')->insert([
            'nombre' => 'Fiebre aftosa',
            'tipo_animal' => [0],
            'intervalo_dosis' => 182,
        ]);
        
        DB::table('vacunas')->insert([
            'nombre' => 'Costridial',
            'tipo_animal' => [0],
            'intervalo_dosis' => 180,
        ]);
        
        DB::table('vacunas')->insert([
            'nombre' => 'Rabia',
            'tipo_animal' => [0],
            'intervalo_dosis' => 365,
        ]);
        
        DB::table('vacunas')->insert([
            'nombre' => 'Leptospirosis',
            'tipo_animal' => [4,3],
            'intervalo_dosis' => 365,
        ]);
        
        DB::table('vacunas')->insert([
            'nombre' => 'IBR',
            'tipo_animal' => [4,3],
            'intervalo_dosis' => 365,
        ]);
        
        DB::table('vacunas')->insert([
            'nombre' => 'Diarrea viral bobina',
            'tipo_animal' => [4,3],
            'intervalo_dosis' => 365,
        ]);
    }
}
