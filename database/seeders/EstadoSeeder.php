<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstadoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('estados')->insert(['estado' => 'sano']);
        DB::table('estados')->insert(['estado' => 'fallecido']);
        DB::table('estados')->insert(['estado' => 'gestacion']);
        DB::table('estados')->insert(['estado' => 'lactancia']);
        DB::table('estados')->insert(['estado' => 'vendido']);
        DB::table('estados')->insert(['estado' => 'pendiente_revision']);
        DB::table('estados')->insert(['estado' => 'pendiente_servicio']);
        DB::table('estados')->insert(['estado' => 'pendiente_secar']);
        DB::table('estados')->insert(['estado' => 'pendiente_numeracion']);
        DB::table('estados')->insert(['estado' => 'pendiente_capar']);
        DB::table('estados')->insert(['estado' => 'pendiente_pesaje_leche']);
    }
}
