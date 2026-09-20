<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RbacSeeder extends Seeder
{
    /**
     * Every module that is protected by `permission:<module>.<action>` middleware.
     * Adding a module here is all that is needed to grant super-admin access to it.
     */
    public const MODULES = ['setting', 'role', 'permission', 'user', 'activity'];

    public const ACTIONS = ['view', 'create', 'update', 'delete'];

    public const SUPER_ADMIN = 'super-admin';

    /** The activity log is read-only by design. */
    public const MODULE_ACTIONS = ['activity' => ['view']];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(self::permissionNames())
            ->map(fn (string $name) => Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]));

        $role = Role::firstOrCreate(['name' => self::SUPER_ADMIN, 'guard_name' => 'web']);
        $role->syncPermissions($permissions);

        $this->seedAdmin($role);
    }

    /**
     * @return array<int, string>
     */
    public static function permissionNames(): array
    {
        $names = [];

        foreach (self::MODULES as $module) {
            foreach (self::MODULE_ACTIONS[$module] ?? self::ACTIONS as $action) {
                $names[] = "{$module}.{$action}";
            }
        }

        return $names;
    }

    private function seedAdmin(Role $role): void
    {
        $email = config('service-contract.admin.email');
        $password = config('service-contract.admin.password');
        $generated = blank($password);

        if ($generated) {
            $password = Str::password(16);
        }

        $admin = User::where('email', $email)->first();

        if ($admin) {
            $admin->assignRole($role);
            $this->command?->info("Admin {$email} already exists; role ensured, password untouched.");

            return;
        }

        $admin = User::create([
            'name' => config('service-contract.admin.name'),
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        $admin->assignRole($role);

        if ($generated) {
            $this->command?->warn("Generated admin password for {$email}: {$password}");
            $this->command?->warn('This is shown once. Store it now, or set ADMIN_PASSWORD before seeding.');

            return;
        }

        $this->command?->info("Admin {$email} created with the password from ADMIN_PASSWORD.");
    }
}
