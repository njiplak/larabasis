<?php

use App\Contract\Setting\UserContract;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;

dataset('softDeletable', [
    'setting' => ['setting'],
    'user' => ['user'],
]);

function makeSoftDeletable(string $module, string $value): int
{
    return match ($module) {
        'setting' => Setting::create(['key' => $value, 'value' => 'v'])->id,
        'user' => User::factory()->create(['name' => $value])->id,
    };
}

function findTrashed(string $module, int $id)
{
    return match ($module) {
        'setting' => Setting::withTrashed()->find($id),
        'user' => User::withTrashed()->find($id),
    };
}

beforeEach(function () {
    $this->actor = superAdmin();
});

test('deleting only soft-deletes the row', function (string $module) {
    $id = makeSoftDeletable($module, 'to-delete');

    $this->actingAs($this->actor)
        ->delete(route("backoffice.setting.{$module}.destroy", ['id' => $id]));

    expect(findTrashed($module, $id))->not->toBeNull();
    expect(findTrashed($module, $id)->trashed())->toBeTrue();
})->with('softDeletable');

test('a soft-deleted row is hidden from the default listing', function (string $module) {
    $id = makeSoftDeletable($module, 'hidden-row');
    $this->actingAs($this->actor)->delete(route("backoffice.setting.{$module}.destroy", ['id' => $id]));

    $items = $this->actingAs($this->actor)
        ->getJson(route("backoffice.setting.{$module}.fetch"))
        ->assertOk()
        ->json('items');

    expect(collect($items)->pluck('id'))->not->toContain($id);
})->with('softDeletable');

test('the trashed filter can include or isolate deleted rows', function (string $module) {
    $kept = makeSoftDeletable($module, 'kept-row');
    $deleted = makeSoftDeletable($module, 'deleted-row');
    $this->actingAs($this->actor)->delete(route("backoffice.setting.{$module}.destroy", ['id' => $deleted]));

    $with = collect($this->actingAs($this->actor)
        ->getJson(route("backoffice.setting.{$module}.fetch").'?filter[trashed]=with')
        ->json('items'))->pluck('id');

    $only = collect($this->actingAs($this->actor)
        ->getJson(route("backoffice.setting.{$module}.fetch").'?filter[trashed]=only')
        ->json('items'))->pluck('id');

    expect($with)->toContain($kept)->toContain($deleted);
    expect($only)->toContain($deleted)->not->toContain($kept);
})->with('softDeletable');

test('a deleted row can be restored', function (string $module) {
    $id = makeSoftDeletable($module, 'restore-me');
    $this->actingAs($this->actor)->delete(route("backoffice.setting.{$module}.destroy", ['id' => $id]));

    $this->actingAs($this->actor)
        ->post(route("backoffice.setting.{$module}.restore", ['id' => $id]))
        ->assertSessionHasNoErrors();

    expect(findTrashed($module, $id)->trashed())->toBeFalse();
})->with('softDeletable');

test('restoring is logged', function (string $module) {
    $id = makeSoftDeletable($module, 'logged-restore');
    $this->actingAs($this->actor)->delete(route("backoffice.setting.{$module}.destroy", ['id' => $id]));
    $this->actingAs($this->actor)->post(route("backoffice.setting.{$module}.restore", ['id' => $id]));

    expect(Activity::where('event', 'restored')->where('subject_id', $id)->exists())->toBeTrue();
})->with('softDeletable');

test('restoring a row that is not deleted 404s', function (string $module) {
    $id = makeSoftDeletable($module, 'never-deleted');

    $this->actingAs($this->actor)
        ->post(route("backoffice.setting.{$module}.restore", ['id' => $id]))
        ->assertNotFound();
})->with('softDeletable');

test('restore needs the delete permission', function (string $module) {
    $actor = userWith(["{$module}.view"]);
    $id = makeSoftDeletable($module, 'guarded-restore');
    findTrashed($module, $id)->delete();

    $this->actingAs($actor)
        ->post(route("backoffice.setting.{$module}.restore", ['id' => $id]))
        ->assertForbidden();

    expect(findTrashed($module, $id)->trashed())->toBeTrue();
})->with('softDeletable');

test('a soft-deleted user can no longer log in', function () {
    $user = User::factory()->create([
        'email' => 'gone@example.com',
        'password' => Hash::make('still-the-password'),
    ]);

    // Deleted through the service, not the HTTP route: acting as an admin
    // would leave that admin authenticated and the guest middleware would
    // short-circuit the login attempt under test.
    app(UserContract::class)->destroy($user->id);

    $this->post(route('attempt'), [
        'email' => 'gone@example.com',
        'password' => 'still-the-password',
    ])->assertSessionHasErrors('errors');

    $this->assertGuest();
});

test('an existing session stops working once the user is soft-deleted', function () {
    $user = User::factory()->create([
        'email' => 'session@example.com',
        'password' => Hash::make('still-the-password'),
    ]);

    // A real login, not actingAs(): actingAs pins the user on the guard for
    // the rest of the test, which would hide the session lookup under test.
    $this->post(route('attempt'), [
        'email' => 'session@example.com',
        'password' => 'still-the-password',
    ]);
    $this->get(route('backoffice.index'))->assertOk();

    // Deleted on the model, not through the service: the service refuses to
    // delete the acting user, which is exactly what AdminGuardTest covers.
    $user->delete();

    // The guard caches the resolved user for the life of the app instance;
    // a real next request starts without it.
    $this->app['auth']->forgetGuards();

    $this->get(route('backoffice.index'))->assertRedirect(route('login'));
});

test('bulk delete also soft-deletes rather than erasing', function (string $module) {
    $first = makeSoftDeletable($module, 'bulk-one');
    $second = makeSoftDeletable($module, 'bulk-two');

    $this->actingAs($this->actor)
        ->post(route("backoffice.setting.{$module}.destroy-bulk"), ['ids' => [$first, $second]]);

    expect(findTrashed($module, $first)->trashed())->toBeTrue();
    expect(findTrashed($module, $second)->trashed())->toBeTrue();
})->with('softDeletable');

test('a deleted users email stays reserved, with a message pointing at restore', function () {
    $deleted = User::factory()->create(['email' => 'reserved@example.com']);
    $this->actingAs($this->actor)->delete(route('backoffice.setting.user.destroy', ['id' => $deleted->id]));

    $response = $this->actingAs($this->actor)->post(route('backoffice.setting.user.store'), [
        'name' => 'Reuse',
        'email' => 'reserved@example.com',
        'password' => 'password-long-enough',
    ])->assertSessionHasErrors('email');

    expect($response->getSession()->get('errors')->first('email'))
        ->toContain('deleted user')
        ->toContain('Restore');
});

test('an email in use by a live user gets the ordinary message', function () {
    User::factory()->create(['email' => 'live@example.com']);

    $response = $this->actingAs($this->actor)->post(route('backoffice.setting.user.store'), [
        'name' => 'Reuse',
        'email' => 'live@example.com',
        'password' => 'password-long-enough',
    ])->assertSessionHasErrors('email');

    expect($response->getSession()->get('errors')->first('email'))
        ->toBe('That email address is already in use.');
});

test('a soft-deleted row is not reachable through the actions the table offers', function (string $module) {
    $actor = superAdmin();
    $id = makeSoftDeletable($module, 'trashed-actions');
    findTrashed($module, $id)->delete();

    // Both of these 404, which is why IndexPage hides Detail and Delete on a
    // trashed row and leaves only Restore.
    $this->actingAs($actor)
        ->get(route("backoffice.setting.{$module}.show", ['id' => $id]))
        ->assertNotFound();

    $this->actingAs($actor)
        ->delete(route("backoffice.setting.{$module}.destroy", ['id' => $id]))
        ->assertNotFound();

    // Restore is the action that does work.
    $this->actingAs($actor)
        ->post(route("backoffice.setting.{$module}.restore", ['id' => $id]))
        ->assertSessionHasNoErrors();

    expect(findTrashed($module, $id)->trashed())->toBeFalse();
})->with('softDeletable');
