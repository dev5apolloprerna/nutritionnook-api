<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('modules')) {
            Schema::create('modules', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('role_id')->constrained()->cascadeOnDelete();
                $table->foreignId('module_id')->nullable()->constrained('modules')->cascadeOnDelete();
                $table->boolean('list_permission')->default(false);
                $table->boolean('create_permission')->default(false);
                $table->boolean('edit_permission')->default(false);
                $table->boolean('delete_permission')->default(false);
                $table->boolean('no_permission')->default(false);
                $table->timestamps();
                $table->unique(['role_id', 'module_id']);
            });
        }

        $roleId = DB::table('roles')->where('title', 'Food Inspector')->value('id');
        if ($roleId) {
            DB::table('roles')->where('id', $roleId)->update([
                'status' => 'active',
                'deleted_at' => null,
                'updated_at' => now(),
            ]);
        } else {
            $roleId = DB::table('roles')->insertGetId([
                'title' => 'Food Inspector',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $moduleId = DB::table('modules')->where('name', 'Chefs')->value('id');
        if ($moduleId) {
            DB::table('modules')->where('id', $moduleId)->update([
                'deleted_at' => null,
                'updated_at' => now(),
            ]);
        } else {
            $moduleId = DB::table('modules')->insertGetId([
                'name' => 'Chefs',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('permissions')->updateOrInsert(
            ['role_id' => $roleId, 'module_id' => $moduleId],
            [
                'list_permission' => true,
                'create_permission' => false,
                'edit_permission' => true,
                'delete_permission' => false,
                'no_permission' => false,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        // These tables may predate this migration in deployed databases.
    }
};