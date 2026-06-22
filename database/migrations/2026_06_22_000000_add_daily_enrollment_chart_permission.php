<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private string $permissionName = 'View:DailyEnrollmentChart';

    public function up(): void
    {
        $guardName = config('auth.defaults.guard', 'web');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name' => $this->permissionName,
            'guard_name' => $guardName,
        ]);

        Role::query()
            ->where('guard_name', $guardName)
            ->whereIn('name', [
                config('filament-shield.super_admin.name', 'super_admin'),
                'viewer',
                'beheerder',
            ])
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permission));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $guardName = config('auth.defaults.guard', 'web');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::query()
            ->where('name', $this->permissionName)
            ->where('guard_name', $guardName)
            ->first();

        if (! $permission) {
            return;
        }

        Role::query()
            ->where('guard_name', $guardName)
            ->whereIn('name', [
                config('filament-shield.super_admin.name', 'super_admin'),
                'viewer',
                'beheerder',
            ])
            ->get()
            ->each(fn (Role $role) => $role->revokePermissionTo($permission));

        $permission->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
