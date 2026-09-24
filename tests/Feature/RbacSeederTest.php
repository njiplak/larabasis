<?php

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('seeding produces an admin who can reach every admin screen', function () {
    config(['service-contract.admin.password' => 'seeded-admin-password']);

    $this->seed();

    $admin = User::where('email', config('service-contract.admin.email'))->firstOrFail();

    expect($admin->hasRole(RbacSeeder::SUPER_ADMIN))->toBeTrue();

    foreach (['setting', 'role', 'permission', 'user'] as $module) {
        $this->actingAs($admin)
            ->get(route("backoffice.setting.{$module}.index"))
            ->assertOk();
    }
});

test('the seeded admin can log in with the configured password', function () {
    config(['service-contract.admin.password' => 'seeded-admin-password']);

    $this->seed();

    $this->post(route('attempt'), [
        'email' => config('service-contract.admin.email'),
        'password' => 'seeded-admin-password',
    ])->assertRedirect(route('backoffice.index'));

    $this->assertAuthenticated();
});

test('every declared permission is created and granted to super-admin', function () {
    $this->seed();

    $names = RbacSeeder::permissionNames();

    expect(Permission::whereIn('name', $names)->count())->toBe(count($names));
    expect(Role::where('name', RbacSeeder::SUPER_ADMIN)->firstOrFail()->permissions->count())
        ->toBe(count($names));
});

test('the activity module is view only', function () {
    $this->seed();

    expect(RbacSeeder::permissionNames())
        ->toContain('activity.view')
        ->not->toContain('activity.delete');
});

test('re-seeding is idempotent and never rewrites an existing admin password', function () {
    config(['service-contract.admin.password' => 'seeded-admin-password']);
    $this->seed();

    $admin = User::where('email', config('service-contract.admin.email'))->firstOrFail();
    $admin->forceFill(['password' => Hash::make('changed-by-the-user')])->save();

    config(['service-contract.admin.password' => 'a-different-password']);
    $this->seed();

    expect(User::where('email', config('service-contract.admin.email'))->count())->toBe(1);
    expect(Hash::check('changed-by-the-user', $admin->fresh()->password))->toBeTrue();
    expect(Permission::whereIn('name', RbacSeeder::permissionNames())->count())
        ->toBe(count(RbacSeeder::permissionNames()));
});

test('a generated password is used when none is configured', function () {
    config(['service-contract.admin.password' => null]);

    $this->seed();

    $admin = User::where('email', config('service-contract.admin.email'))->firstOrFail();

    expect($admin->password)->not->toBeEmpty();
    expect(Hash::check('', $admin->password))->toBeFalse();
});
