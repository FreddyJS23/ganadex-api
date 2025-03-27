<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GanadoTipoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('ganado_tipos')->insert(['tipo' => 'Becerro']);
        DB::table('ganado_tipos')->insert(['tipo' => 'Maute']);
        DB::table('ganado_tipos')->insert(['tipo' => 'Novillo']);
        DB::table('ganado_tipos')->insert(['tipo' => 'Adulto']);
    }
}
