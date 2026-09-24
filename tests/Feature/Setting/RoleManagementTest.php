<?php

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->actor = superAdmin();
});

test('a role is created with its permissions attached', function () {
    $view = Permission::firstOrCreate(['name' => 'setting.view', 'guard_name' => 'web']);
    $create = Permission::firstOrCreate(['name' => 'setting.create', 'guard_name' => 'web']);

    $this->actingAs($this->actor)->post(route('backoffice.setting.role.store'), [
        'name' => 'content-editor',
        'permissions' => [$view->id, $create->id],
    ]);

    $role = Role::where('name', 'content-editor')->firstOrFail();

    expect($role->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['setting.create', 'setting.view']);
});

test('updating a role syncs rather than appends permissions', function () {
    $view = Permission::firstOrCreate(['name' => 'setting.view', 'guard_name' => 'web']);
    $create = Permission::firstOrCreate(['name' => 'setting.create', 'guard_name' => 'web']);
    $role = Role::create(['name' => 'shrinking', 'guard_name' => 'web']);
    $role->syncPermissions([$view, $create]);

    $this->actingAs($this->actor)->put(route('backoffice.setting.role.update', ['id' => $role->id]), [
        'name' => 'shrinking',
        'permissions' => [$view->id],
    ]);

    expect($role->fresh()->permissions->pluck('name')->all())->toBe(['setting.view']);
});

test('a role can have every permission removed', function () {
    $view = Permission::firstOrCreate(['name' => 'setting.view', 'guard_name' => 'web']);
    $role = Role::create(['name' => 'stripped', 'guard_name' => 'web']);
    $role->syncPermissions([$view]);

    $this->actingAs($this->actor)->put(route('backoffice.setting.role.update', ['id' => $role->id]), [
        'name' => 'stripped',
    ]);

    expect($role->fresh()->permissions)->toBeEmpty();
});

test('a duplicate role name is rejected', function () {
    Role::create(['name' => 'taken', 'guard_name' => 'web']);

    $this->actingAs($this->actor)
        ->post(route('backoffice.setting.role.store'), ['name' => 'taken'])
        ->assertSessionHasErrors('name');
});

test('an unknown permission id is rejected', function () {
    $this->actingAs($this->actor)
        ->post(route('backoffice.setting.role.store'), [
            'name' => 'bad-permissions',
            'permissions' => [999999],
        ])
        ->assertSessionHasErrors('permissions.0');
});

test('the role form exposes permissions grouped by module', function () {
    Permission::firstOrCreate(['name' => 'setting.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'user.view', 'guard_name' => 'web']);

    $this->actingAs($this->actor)
        ->get(route('backoffice.setting.role.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('setting/role/form')
            ->has('permissions')
        );
});
