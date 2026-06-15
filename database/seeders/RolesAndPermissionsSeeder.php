<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Seed the initial Shield roles and permissions.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guardName = config('auth.defaults.guard', 'web');

        $resourcePermissions = $this->resourcePermissions([
            'Enrollment',
            'Event',
            'Partner',
            'User',
            'Vereniging',
            'Role',
        ]);

        $dashboardPermissions = [
            'View:EventEnrollmentOverview',
            'View:OrganizationEnrollmentTotals',
            'View:MyProfilePage',
        ];

        $customPermissions = collect(config('filament-shield.custom_permissions', []))
            ->map(fn (string $label, int|string $key): string => is_int($key) ? $label : $key)
            ->values()
            ->all();

        $allPermissions = collect([
            ...$resourcePermissions,
            ...$dashboardPermissions,
            ...$customPermissions,
        ])->unique()->values();

        $permissionModels = $allPermissions
            ->map(fn (string $permission): Permission => Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guardName,
            ]));

        Role::firstOrCreate([
            'name' => config('filament-shield.super_admin.name', 'super_admin'),
            'guard_name' => $guardName,
        ])->syncPermissions($permissionModels);

        Role::firstOrCreate([
            'name' => config('filament-shield.panel_user.name', 'panel_user'),
            'guard_name' => $guardName,
        ])->syncPermissions(['View:MyProfilePage']);

        Role::firstOrCreate([
            'name' => 'viewer',
            'guard_name' => $guardName,
        ])->syncPermissions([
            ...$this->resourcePermissions(['Enrollment', 'Event', 'Partner', 'Vereniging'], ['viewAny', 'view']),
            ...$dashboardPermissions,
        ]);

        Role::firstOrCreate([
            'name' => 'beheerder',
            'guard_name' => $guardName,
        ])->syncPermissions([
            ...$this->resourcePermissions(['Enrollment', 'Event', 'Partner', 'Vereniging']),
            ...$dashboardPermissions,
            ...$customPermissions,
        ]);

        $admin = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'test@example.com')],
            [
                'name' => env('ADMIN_NAME', 'Test User'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
            ],
        );

        $admin->assignRole(config('filament-shield.super_admin.name', 'super_admin'));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  array<int, string>  $subjects
     * @param  array<int, string>|null  $methods
     * @return array<int, string>
     */
    private function resourcePermissions(array $subjects, ?array $methods = null): array
    {
        $methods ??= config('filament-shield.policies.methods', []);
        $separator = config('filament-shield.permissions.separator', ':');

        return collect($subjects)
            ->flatMap(fn (string $subject): array => collect($methods)
                ->map(fn (string $method): string => Str::studly($method).$separator.$subject)
                ->all())
            ->all();
    }
}
