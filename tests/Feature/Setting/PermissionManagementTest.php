<?php

use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->actor = superAdmin();
});

test('a permission is created in module.action format', function () {
    $this->actingAs($this->actor)
        ->post(route('backoffice.setting.permission.store'), ['name' => 'campaign.create'])
        ->assertSessionHasNoErrors();

    expect(Permission::where('name', 'campaign.create')->exists())->toBeTrue();
});

test('a malformed permission name is rejected', function (string $name) {
    $this->actingAs($this->actor)
        ->post(route('backoffice.setting.permission.store'), ['name' => $name])
        ->assertSessionHasErrors('name');
})->with([
    'no dot' => 'campaign',
    'uppercase' => 'Campaign.Create',
    'too many parts' => 'a.b.c',
    'digits' => 'campaign.create2',
    'empty' => '',
]);

test('a duplicate permission name is rejected', function () {
    Permission::firstOrCreate(['name' => 'campaign.view', 'guard_name' => 'web']);

    $this->actingAs($this->actor)
        ->post(route('backoffice.setting.permission.store'), ['name' => 'campaign.view'])
        ->assertSessionHasErrors('name');
});

test('a permission can be renamed', function () {
    $permission = Permission::firstOrCreate(['name' => 'campaign.view', 'guard_name' => 'web']);

    $this->actingAs($this->actor)
        ->put(route('backoffice.setting.permission.update', ['id' => $permission->id]), ['name' => 'campaign.read'])
        ->assertSessionHasNoErrors();

    expect($permission->fresh()->name)->toBe('campaign.read');
});
