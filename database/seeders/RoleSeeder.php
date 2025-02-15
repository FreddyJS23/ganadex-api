<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roleAdmin = Role::create(['name' => 'admin']);
        $roleVeterinario = Role::create(['name' => 'veterinario']);

        $permissionAdmin = Permission::create(['name' => 'todos']);

        $permissionVerTodasFincas = Permission::create(['name' => 'observacion fincas']);

        /*
        puede crear,ver partos
        puede crear,ver revisiones
        puede crear,ver servicios
        puede ver ganado
        puede ver toro
        puede ver descartes
        puede ver jornadas vacunacion
        editar su usuario
         */
        $permissionVeterinario = Permission::create(['name' => 'limitado']);

        $roleAdmin->givePermissionTo($permissionAdmin);

        $roleVeterinario->givePermissionTo($permissionVeterinario);
    }
}
