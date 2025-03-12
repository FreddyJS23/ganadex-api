<?php

namespace Database\Seeders;

use App\Models\PreguntasSeguridad;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PreguntasSeguridadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        DB::table('preguntas_seguridad')->insert(['pregunta' => '¿Cómo se llamaba tu primera mascota?']);
        DB::table('preguntas_seguridad')->insert(['pregunta' => '¿Cuál era el apodo de tu hermano/a menor?']);
        DB::table('preguntas_seguridad')->insert(['pregunta' => '¿Qué deporte practicabas en la secundaria?']);
        DB::table('preguntas_seguridad')->insert(['pregunta' => '¿Cuál era el nombre de tu profesor/a favorito/a?']);
        DB::table('preguntas_seguridad')->insert(['pregunta' => '¿En qué mes te casaste?']);
        DB::table('preguntas_seguridad')->insert(['pregunta' => '¿Qué deporte le gusta más?']);
        DB::table('preguntas_seguridad')->insert(['pregunta' => '¿En qué ciudad se conocieron sus padres?']);
        DB::table('preguntas_seguridad')->insert(['pregunta' => '¿Cuál era el apodo que te ponían de niño?']);
        DB::table('preguntas_seguridad')->insert(['pregunta' => '¿En qué hospital o ciudad naciste?']);
        DB::table('preguntas_seguridad')->insert(['pregunta' => '¿Qué instrumento musical siempre quisiste aprender a tocar?']);
        DB::table('preguntas_seguridad')->insert(['pregunta' => '¿Qué comida odiabas de niño pero ahora te encanta?']);

    }
}
