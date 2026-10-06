<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    // Users are soft deleted: a hard delete would cascade to their trainer and payout history.
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Whoever may delete users may also restore them.
        foreach (['Restore:User', 'RestoreAny:User'] as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

            Role::whereHas('permissions', fn ($query) => $query->where('name', 'Delete:User'))
                ->each(fn (Role $role) => $role->givePermissionTo($permission));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Permission::whereIn('name', ['Restore:User', 'RestoreAny:User'])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
