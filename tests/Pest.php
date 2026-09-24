<?php

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * A user holding exactly the given permissions, via a throwaway role.
 */
function userWith(array $permissions = []): User
{
    $user = User::factory()->create();

    if ($permissions !== []) {
        $role = Role::create(['name' => 'test-'.Str::random(8), 'guard_name' => 'web']);

        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]));
        }

        $user->assignRole($role);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user;
}

/**
 * A user holding every permission the app defines.
 */
function superAdmin(): User
{
    return userWith(RbacSeeder::permissionNames());
}
