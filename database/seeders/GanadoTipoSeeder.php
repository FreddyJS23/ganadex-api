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
        DB::table('ganado_tipos')->insert(['tipo' => 'becerro']);
        DB::table('ganado_tipos')->insert(['tipo' => 'maute']);
        DB::table('ganado_tipos')->insert(['tipo' => 'novillo']);
        DB::table('ganado_tipos')->insert(['tipo' => 'adulto']);
        DB::table('ganado_tipos')->insert(['tipo' => 'res']);
    }
}
