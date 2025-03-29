<?php

namespace Database\Seeders;

use App\Models\TipoRevision;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoRevisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('tipo_revisions')->insert(['tipo' => 'Gestación']);
        DB::table('tipo_revisions')->insert(['tipo' => 'Descartar']);
        DB::table('tipo_revisions')->insert(['tipo' => 'Aborto']);
        DB::table('tipo_revisions')->insert(['tipo' => 'Rutina']);

    }
}
