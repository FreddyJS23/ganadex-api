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

        /* se usa json_encode para poder almacenar un array de strings
        como el modelo vacuna ya tiene un cast para el array no es necesario usar
        el json_decode para obtener el array de strings */

        DB::table("vacunas")->insert([
            "nombre" => "Fiebre aftosa",
            "tipo_animal" => json_encode(["rebano"]),
            "intervalo_dosis" => 182,
        ]);

        DB::table("vacunas")->insert([
            "nombre" => "Costridial",
            "tipo_animal" => json_encode(["rebano"]),
            "intervalo_dosis" => 180,
        ]);

        DB::table("vacunas")->insert([
            "nombre" => "Rabia",
            "tipo_animal" => json_encode(["rebano"]),
            "intervalo_dosis" => 365,
        ]);

        DB::table("vacunas")->insert([
            "nombre" => "Leptospirosis",
            "tipo_animal" => json_encode(["novillo", "adulto"]),
            "intervalo_dosis" => 365,
        ]);

        DB::table("vacunas")->insert([
            "nombre" => "RB51",
            "tipo_animal" => json_encode(["rebano"]),
            "intervalo_dosis" => 20,
        ]);

        DB::table("vacunas")->insert([
            "nombre" => "RB51",
            "tipo_animal" => json_encode(["becerras"]),
            "intervalo_dosis" => 150,
        ]);

        DB::table("vacunas")->insert([
            "nombre" => "IBR",
            "tipo_animal" => json_encode(["novillo", "adulto"]),
            "intervalo_dosis" => 365,
        ]);

        DB::table("vacunas")->insert([
            "nombre" => "Diarrea viral bobina",
            "tipo_animal" => json_encode(["novillo", "adulto"]),
            "intervalo_dosis" => 365,
        ]);
    }
}
