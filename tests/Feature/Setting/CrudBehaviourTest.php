<?php

use App\Models\Setting;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Every setting module is built from the same controller + BaseService template,
 * so every module gets the same battery. No spot-checking.
 */
dataset('modules', [
    'setting' => ['setting', 'key'],
    'role' => ['role', 'name'],
    'permission' => ['permission', 'name'],
    'user' => ['user', 'name'],
]);

/**
 * @return array<int, int> ids of the created records
 */
function makeRecords(string $module, array $values): array
{
    return array_map(fn (string $value) => match ($module) {
        'setting' => Setting::create(['key' => $value, 'value' => 'v'])->id,
        'role' => Role::create(['name' => $value, 'guard_name' => 'web'])->id,
        'permission' => Permission::create(['name' => "mod.{$value}", 'guard_name' => 'web'])->id,
        'user' => User::factory()->create(['name' => $value])->id,
    }, $values);
}

function countRecords(string $module): int
{
    return match ($module) {
        'setting' => Setting::count(),
        'role' => Role::count(),
        'permission' => Permission::count(),
        'user' => User::count(),
    };
}

function routeFor(string $module, string $action, array $params = []): string
{
    return route("backoffice.setting.{$module}.{$action}", $params);
}

test('the index screen loads for a permitted user', function (string $module) {
    $this->actingAs(userWith(["{$module}.view"]))
        ->get(routeFor($module, 'index'))
        ->assertOk();
})->with('modules');

test('the index screen is forbidden without the view permission', function (string $module) {
    $this->actingAs(userWith([]))
        ->get(routeFor($module, 'index'))
        ->assertForbidden();
})->with('modules');

test('a guest is redirected to login', function (string $module) {
    $this->get(routeFor($module, 'index'))->assertRedirect(route('login'));
})->with('modules');

test('fetch returns a paginated envelope', function (string $module) {
    makeRecords($module, ['alpha', 'bravo']);

    $response = $this->actingAs(userWith(["{$module}.view"]))
        ->getJson(routeFor($module, 'fetch'))
        ->assertOk();

    expect($response->json())
        ->toHaveKeys(['items', 'current_page', 'next_page', 'prev_page', 'total_page', 'per_page']);
    expect($response->json('items'))->not->toBeEmpty();
})->with('modules');

test('search returns only matching rows', function (string $module, string $column) {
    makeRecords($module, ['findme', 'somethingelse']);

    $items = $this->actingAs(userWith(["{$module}.view"]))
        ->getJson(routeFor($module, 'fetch').'?filter[search]=findme')
        ->assertOk()
        ->json('items');

    expect($items)->toHaveCount(1);
    expect($items[0][$column])->toContain('findme');
})->with('modules');

test('sorting is applied', function (string $module, string $column) {
    makeRecords($module, ['aaa', 'zzz']);

    $ascending = $this->actingAs(userWith(["{$module}.view"]))
        ->getJson(routeFor($module, 'fetch')."?sort={$column}")
        ->assertOk()
        ->json("items.0.{$column}");

    $descending = $this->actingAs(userWith(["{$module}.view"]))
        ->getJson(routeFor($module, 'fetch')."?sort=-{$column}")
        ->assertOk()
        ->json("items.0.{$column}");

    expect($ascending)->not->toBe($descending);
})->with('modules');

test('an unknown filter fails with 400 instead of a 200 error body', function (string $module) {
    $this->actingAs(userWith(["{$module}.view"]))
        ->getJson(routeFor($module, 'fetch').'?filter[nope]=x')
        ->assertStatus(400);
})->with('modules');

test('a missing record 404s instead of rendering a page', function (string $module) {
    $this->actingAs(userWith(["{$module}.view", "{$module}.update"]))
        ->get(routeFor($module, 'show', ['id' => 999999]))
        ->assertNotFound();
})->with('modules');

test('bulk delete actually deletes the selected rows', function (string $module) {
    // The actor is created first: userWith() itself adds user/role/permission rows.
    $actor = userWith(["{$module}.delete"]);
    $ids = makeRecords($module, ['one', 'two', 'three']);
    $before = countRecords($module);

    $this->actingAs($actor)
        ->post(routeFor($module, 'destroy-bulk'), ['ids' => [$ids[0], $ids[1]]]);

    expect(countRecords($module))->toBe($before - 2);
})->with('modules');

test('bulk delete rejects an empty or missing selection', function (string $module) {
    $user = userWith(["{$module}.delete"]);

    $this->actingAs($user)
        ->post(routeFor($module, 'destroy-bulk'), [])
        ->assertSessionHasErrors('ids');

    $this->actingAs($user)
        ->post(routeFor($module, 'destroy-bulk'), ['ids' => []])
        ->assertSessionHasErrors('ids');
})->with('modules');

test('bulk delete is forbidden without the delete permission', function (string $module) {
    $actor = userWith(["{$module}.view"]);
    $ids = makeRecords($module, ['one']);
    $before = countRecords($module);

    $this->actingAs($actor)
        ->post(routeFor($module, 'destroy-bulk'), ['ids' => $ids])
        ->assertForbidden();

    expect(countRecords($module))->toBe($before);
})->with('modules');

test('single delete removes the row', function (string $module) {
    $actor = userWith(["{$module}.delete"]);
    $ids = makeRecords($module, ['gone']);
    $before = countRecords($module);

    $this->actingAs($actor)
        ->delete(routeFor($module, 'destroy', ['id' => $ids[0]]));

    expect(countRecords($module))->toBe($before - 1);
})->with('modules');

test('deleting a missing row 404s', function (string $module) {
    $this->actingAs(userWith(["{$module}.delete"]))
        ->delete(routeFor($module, 'destroy', ['id' => 999999]))
        ->assertNotFound();
})->with('modules');

test('each write action is guarded by its own permission', function (string $module) {
    $user = userWith(["{$module}.view"]);

    $this->actingAs($user)->get(routeFor($module, 'create'))->assertForbidden();
    $this->actingAs($user)->post(routeFor($module, 'store'), [])->assertForbidden();
    $this->actingAs($user)->put(routeFor($module, 'update', ['id' => 1]), [])->assertForbidden();
    $this->actingAs($user)->delete(routeFor($module, 'destroy', ['id' => 1]))->assertForbidden();
})->with('modules');
