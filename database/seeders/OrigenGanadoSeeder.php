<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrigenGanadoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('origen_ganados')->insert([
            'origen' => 'Local',
        ]);

        DB::table('origen_ganados')->insert([
            'origen' => 'Externo',
        ]);
    }
}
