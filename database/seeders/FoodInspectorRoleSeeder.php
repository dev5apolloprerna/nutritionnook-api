<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class FoodInspectorRoleSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::withTrashed()->firstOrNew(['title' => 'Food Inspector']);
        $role->status = 'active';
        $role->deleted_at = null;
        $role->save();

        $chefModule = Module::withTrashed()->firstOrNew(['name' => 'Chefs']);
        $chefModule->deleted_at = null;
        $chefModule->save();

        Permission::updateOrCreate(
            ['role_id' => $role->id, 'module_id' => $chefModule->id],
            [
                'list_permission' => true,
                'create_permission' => false,
                'edit_permission' => true,
                'delete_permission' => false,
                'no_permission' => false,
            ]
        );
    }
}