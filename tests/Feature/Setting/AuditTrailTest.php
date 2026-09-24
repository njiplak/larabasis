<?php

use App\Models\Setting;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

beforeEach(function () {
    $this->actor = superAdmin();
});

test('creating a record writes an audit entry naming the actor', function () {
    $this->actingAs($this->actor)
        ->post(route('backoffice.setting.setting.store'), ['key' => 'audited', 'value' => 'v']);

    $activity = Activity::latest('id')->first();

    expect($activity->event)->toBe('created');
    expect($activity->subject_type)->toBe(Setting::class);
    expect($activity->causer_id)->toBe($this->actor->id);
});

test('updating a record logs what changed, old and new', function () {
    $setting = Setting::create(['key' => 'audited', 'value' => 'before']);

    $this->actingAs($this->actor)
        ->put(route('backoffice.setting.setting.update', ['id' => $setting->id]), [
            'key' => 'audited',
            'value' => 'after',
        ]);

    $activity = Activity::where('event', 'updated')->latest('id')->firstOrFail();

    expect($activity->properties['old']['value'])->toBe('before');
    expect($activity->properties['attributes']['value'])->toBe('after');
});

test('deleting and bulk deleting are both logged', function () {
    $one = Setting::create(['key' => 'one', 'value' => 'v']);
    $two = Setting::create(['key' => 'two', 'value' => 'v']);
    $three = Setting::create(['key' => 'three', 'value' => 'v']);

    $this->actingAs($this->actor)->delete(route('backoffice.setting.setting.destroy', ['id' => $one->id]));
    $this->actingAs($this->actor)->post(route('backoffice.setting.setting.destroy-bulk'), [
        'ids' => [$two->id, $three->id],
    ]);

    expect(Activity::where('event', 'deleted')->count())->toBe(3);
});

test('a password hash is never copied into the audit log', function () {
    $this->actingAs($this->actor)->post(route('backoffice.setting.user.store'), [
        'name' => 'Audited User',
        'email' => 'audited@example.com',
        'password' => 'a-secret-password-1',
    ]);

    $activity = Activity::where('subject_type', User::class)->latest('id')->firstOrFail();
    $serialised = json_encode($activity->properties);

    expect($serialised)
        ->not->toContain('a-secret-password-1')
        ->not->toContain('password')
        ->not->toContain('remember_token')
        ->not->toContain('two_factor_secret');
});

test('the activity screen is readable with the permission and forbidden without', function () {
    $this->actingAs(userWith(['activity.view']))
        ->get(route('backoffice.setting.activity.index'))
        ->assertOk();

    $this->actingAs(userWith([]))
        ->get(route('backoffice.setting.activity.index'))
        ->assertForbidden();
});

test('the activity feed is newest first and carries the causer', function () {
    $this->actingAs($this->actor)->post(route('backoffice.setting.setting.store'), ['key' => 'first', 'value' => 'v']);
    $this->actingAs($this->actor)->post(route('backoffice.setting.setting.store'), ['key' => 'second', 'value' => 'v']);

    $items = $this->actingAs($this->actor)
        ->getJson(route('backoffice.setting.activity.fetch'))
        ->assertOk()
        ->json('items');

    expect($items[0]['id'])->toBeGreaterThan($items[1]['id']);
    expect($items[0]['causer']['id'])->toBe($this->actor->id);
});

test('the activity feed can be filtered by event', function () {
    $setting = Setting::create(['key' => 'filtered', 'value' => 'v']);
    $this->actingAs($this->actor)->delete(route('backoffice.setting.setting.destroy', ['id' => $setting->id]));
    $this->actingAs($this->actor)->post(route('backoffice.setting.setting.store'), ['key' => 'another', 'value' => 'v']);

    $items = $this->actingAs($this->actor)
        ->getJson(route('backoffice.setting.activity.fetch').'?filter[event]=deleted')
        ->assertOk()
        ->json('items');

    expect($items)->toHaveCount(1);
    expect($items[0]['event'])->toBe('deleted');
});

test('the activity log is read only: no write routes exist', function () {
    expect(fn () => route('backoffice.setting.activity.store'))
        ->toThrow(RouteNotFoundException::class);
    expect(fn () => route('backoffice.setting.activity.destroy', ['id' => 1]))
        ->toThrow(RouteNotFoundException::class);
});

test('every service that overrides create or update still audits', function () {
    $role = Role::create(['name' => 'audited-role', 'guard_name' => 'web']);
    $permission = Permission::firstOrCreate(['name' => 'setting.view', 'guard_name' => 'web']);
    $user = User::factory()->create();

    $this->actingAs($this->actor)->post(route('backoffice.setting.user.store'), [
        'name' => 'U', 'email' => 'u@example.com', 'password' => 'password-long-enough',
    ]);
    $this->actingAs($this->actor)->put(route('backoffice.setting.user.update', ['id' => $user->id]), [
        'name' => 'Renamed', 'email' => $user->email,
    ]);
    $this->actingAs($this->actor)->post(route('backoffice.setting.role.store'), [
        'name' => 'new-role', 'permissions' => [$permission->id],
    ]);
    $this->actingAs($this->actor)->put(route('backoffice.setting.role.update', ['id' => $role->id]), [
        'name' => 'audited-role', 'permissions' => [$permission->id],
    ]);

    expect(Activity::where('subject_type', User::class)->where('event', 'created')->exists())->toBeTrue();
    expect(Activity::where('subject_type', User::class)->where('event', 'updated')->exists())->toBeTrue();
    expect(Activity::where('subject_type', Role::class)->where('event', 'created')->exists())->toBeTrue();
    expect(Activity::where('subject_type', Role::class)->where('event', 'updated')->exists())->toBeTrue();
});

test('a role permission change records the permission set before and after', function () {
    $view = Permission::firstOrCreate(['name' => 'setting.view', 'guard_name' => 'web']);
    $create = Permission::firstOrCreate(['name' => 'setting.create', 'guard_name' => 'web']);
    $role = Role::create(['name' => 'escalating', 'guard_name' => 'web']);
    $role->syncPermissions([$view]);

    $this->actingAs($this->actor)->put(route('backoffice.setting.role.update', ['id' => $role->id]), [
        'name' => 'escalating',
        'permissions' => [$view->id, $create->id],
    ]);

    $activity = Activity::where('subject_type', Role::class)
        ->where('event', 'updated')->latest('id')->firstOrFail();

    expect($activity->properties['old']['permissions'])->toBe(['setting.view']);
    expect($activity->properties['attributes']['permissions'])
        ->toContain('setting.view')
        ->toContain('setting.create');
});
