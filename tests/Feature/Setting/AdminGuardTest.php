<?php

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->actor = superAdmin();
});

test('a user cannot delete their own account', function () {
    $this->actingAs($this->actor)
        ->delete(route('backoffice.setting.user.destroy', ['id' => $this->actor->id]))
        ->assertSessionHasErrors('errors');

    expect(User::find($this->actor->id))->not->toBeNull();
});

test('a user cannot bulk delete themselves along with others', function () {
    $other = User::factory()->create();

    $this->actingAs($this->actor)->post(route('backoffice.setting.user.destroy-bulk'), [
        'ids' => [$this->actor->id, $other->id],
    ])->assertSessionHasErrors('errors');

    expect(User::find($this->actor->id))->not->toBeNull();
    expect(User::find($other->id))->not->toBeNull();
});

test('the last super-admin cannot be deleted', function () {
    $role = Role::create(['name' => RbacSeeder::SUPER_ADMIN, 'guard_name' => 'web']);
    $lastAdmin = User::factory()->create();
    $lastAdmin->assignRole($role);

    $this->actingAs($this->actor)
        ->delete(route('backoffice.setting.user.destroy', ['id' => $lastAdmin->id]))
        ->assertSessionHasErrors('errors');

    expect(User::find($lastAdmin->id))->not->toBeNull();
});

test('a super-admin can be deleted while another remains', function () {
    $role = Role::create(['name' => RbacSeeder::SUPER_ADMIN, 'guard_name' => 'web']);
    $first = User::factory()->create();
    $second = User::factory()->create();
    $first->assignRole($role);
    $second->assignRole($role);

    $this->actingAs($this->actor)
        ->delete(route('backoffice.setting.user.destroy', ['id' => $first->id]));

    expect(User::find($first->id))->toBeNull();
    expect(User::find($second->id))->not->toBeNull();
});

test('bulk deleting every super-admin at once is blocked', function () {
    $role = Role::create(['name' => RbacSeeder::SUPER_ADMIN, 'guard_name' => 'web']);
    $first = User::factory()->create();
    $second = User::factory()->create();
    $first->assignRole($role);
    $second->assignRole($role);

    $this->actingAs($this->actor)->post(route('backoffice.setting.user.destroy-bulk'), [
        'ids' => [$first->id, $second->id],
    ])->assertSessionHasErrors('errors');

    expect(User::whereIn('id', [$first->id, $second->id])->count())->toBe(2);
});

test('an ordinary user is still deletable', function () {
    $ordinary = User::factory()->create();

    $this->actingAs($this->actor)
        ->delete(route('backoffice.setting.user.destroy', ['id' => $ordinary->id]));

    expect(User::find($ordinary->id))->toBeNull();
});

test('the block reason is shown to the admin, not a generic error', function () {
    $response = $this->actingAs($this->actor)
        ->delete(route('backoffice.setting.user.destroy', ['id' => $this->actor->id]));

    expect($response->getSession()->get('errors')->first('errors'))
        ->toContain('your own account');
});
