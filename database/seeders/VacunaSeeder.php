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
            "intervalo_dosis" => 182,
            "dosis_recomendada_anual" => 2,
            "tipo_vacuna" => "plan_sanitario",
            "aplicable_a_todos" => true,
        ]);

        DB::table("vacunas")->insert([
            "nombre" => "Costridial",
            "intervalo_dosis" => 180,
            "dosis_recomendada_anual" => 2,
            "tipo_vacuna" => "plan_sanitario",
            "aplicable_a_todos" => true,
        ]);

        DB::table("vacunas")->insert([
            "nombre" => "Rabia",
            "intervalo_dosis" => 365,
            "dosis_recomendada_anual" => 1,
            "tipo_vacuna" => "plan_sanitario",
            "aplicable_a_todos" => true,
        ]);

        DB::table("vacunas")->insert([
            "nombre" => "Leptospirosis",
            "intervalo_dosis" => 365,
            "dosis_recomendada_anual" => 1,
            "tipo_vacuna" => "plan_sanitario",
            "aplicable_a_todos" => false,
        ]);
        //tipos animal para vacuna
        DB::table("ganado_tipo_vacuna")->insert([
            ["vacuna_id" => 4, "ganado_tipo_id" => 3, "sexo" => "M"],
            ["vacuna_id" => 4, "ganado_tipo_id" => 4, "sexo" => "H"],
        ]);

        DB::table("vacunas")->insert([
            "nombre" => "RB51",
            "intervalo_dosis" => 20,
            "dosis_recomendada_anual" => 18,
            "tipo_vacuna" => "plan_sanitario",
            "aplicable_a_todos" => true,
        ]);

        DB::table("vacunas")->insert([
            "nombre" => "RB51",
            "intervalo_dosis" => 150,
            "dosis_recomendada_anual" => 2,
            "tipo_vacuna" => "plan_sanitario",
            "aplicable_a_todos" => false,
        ]);
        //tipos animal para vacuna
        DB::table("ganado_tipo_vacuna")->insert([
            ["vacuna_id" => 6, "ganado_tipo_id" => 1, "sexo" => "H"],
        ]);

        DB::table("vacunas")->insert([
            "nombre" => "IBR",
            "intervalo_dosis" => 365,
            "dosis_recomendada_anual" => 1,
            "tipo_vacuna" => "plan_sanitario",
            "aplicable_a_todos" => false,
        ]);
        //tipos animal para vacuna
        DB::table("ganado_tipo_vacuna")->insert([
            ["vacuna_id" => 7, "ganado_tipo_id" => 3, "sexo" => "M"],
            ["vacuna_id" => 7, "ganado_tipo_id" => 4, "sexo" => "H"],
        ]);

        DB::table("vacunas")->insert([
            "nombre" => "Diarrea viral bobina",
            "intervalo_dosis" => 365,
            "dosis_recomendada_anual" => 1,
            "tipo_vacuna" => "plan_sanitario",
            "aplicable_a_todos" => false,
        ]);
        //tipos animal para vacuna
        DB::table("ganado_tipo_vacuna")->insert([
            ["vacuna_id" => 8, "ganado_tipo_id" => 3, "sexo" => "M"],
            ["vacuna_id" => 8, "ganado_tipo_id" => 4, "sexo" => "H"],
        ]);
    }
}
