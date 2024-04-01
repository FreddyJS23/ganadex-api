<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TiposNotificacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('tipos_notificacions')->insert(['tipo' => 'revision']);
        DB::table('tipos_notificacions')->insert(['tipo' => 'parto']);
        DB::table('tipos_notificacions')->insert(['tipo' => 'secado']);
    }
}
