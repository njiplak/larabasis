<?php

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->actor = superAdmin();
});

test('a user is created with a hashed password and a role', function () {
    $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);

    $this->actingAs($this->actor)->post(route('backoffice.setting.user.store'), [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'analytical-engine-1',
        'role' => $role->id,
    ]);

    $created = User::where('email', 'ada@example.com')->firstOrFail();

    expect($created->name)->toBe('Ada Lovelace');
    expect($created->password)->not->toBe('analytical-engine-1');
    expect(Hash::check('analytical-engine-1', $created->password))->toBeTrue();
    expect($created->hasRole('editor'))->toBeTrue();
});

test('creating a user requires a password', function () {
    $this->actingAs($this->actor)
        ->post(route('backoffice.setting.user.store'), [
            'name' => 'No Password',
            'email' => 'nopass@example.com',
        ])
        ->assertSessionHasErrors('password');
});

test('a duplicate email is rejected', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($this->actor)
        ->post(route('backoffice.setting.user.store'), [
            'name' => 'Duplicate',
            'email' => 'taken@example.com',
            'password' => 'password-is-long',
        ])
        ->assertSessionHasErrors('email');
});

test('a user keeps their existing password when the field is left blank', function () {
    $user = User::factory()->create(['password' => Hash::make('original-password')]);

    $this->actingAs($this->actor)->put(route('backoffice.setting.user.update', ['id' => $user->id]), [
        'name' => 'Renamed',
        'email' => $user->email,
        'password' => '',
    ]);

    $user->refresh();

    expect($user->name)->toBe('Renamed');
    expect(Hash::check('original-password', $user->password))->toBeTrue();
});

test('a users password can be changed', function () {
    $user = User::factory()->create(['password' => Hash::make('original-password')]);

    $this->actingAs($this->actor)->put(route('backoffice.setting.user.update', ['id' => $user->id]), [
        'name' => $user->name,
        'email' => $user->email,
        'password' => 'a-brand-new-password',
    ]);

    expect(Hash::check('a-brand-new-password', $user->fresh()->password))->toBeTrue();
});

test('updating a user keeps their own email valid', function () {
    $user = User::factory()->create(['email' => 'keep@example.com']);

    $this->actingAs($this->actor)
        ->put(route('backoffice.setting.user.update', ['id' => $user->id]), [
            'name' => 'Same Email',
            'email' => 'keep@example.com',
        ])
        ->assertSessionHasNoErrors();
});

test('a role can be swapped and cleared', function () {
    $editor = Role::create(['name' => 'editor', 'guard_name' => 'web']);
    $viewer = Role::create(['name' => 'viewer', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($editor);

    $payload = ['name' => $user->name, 'email' => $user->email];

    $this->actingAs($this->actor)
        ->put(route('backoffice.setting.user.update', ['id' => $user->id]), $payload + ['role' => $viewer->id]);
    expect($user->fresh()->getRoleNames()->all())->toBe(['viewer']);

    $this->actingAs($this->actor)
        ->put(route('backoffice.setting.user.update', ['id' => $user->id]), $payload);
    expect($user->fresh()->getRoleNames()->all())->toBe([]);
});

test('an unknown role id is rejected', function () {
    $this->actingAs($this->actor)
        ->post(route('backoffice.setting.user.store'), [
            'name' => 'Bad Role',
            'email' => 'badrole@example.com',
            'password' => 'password-is-long',
            'role' => 999999,
        ])
        ->assertSessionHasErrors('role');
});

test('users can be filtered by role', function () {
    $role = Role::create(['name' => 'auditor', 'guard_name' => 'web']);
    User::factory()->create(['name' => 'In Role'])->assignRole($role);
    User::factory()->create(['name' => 'Not In Role']);

    $items = $this->actingAs($this->actor)
        ->getJson(route('backoffice.setting.user.fetch').'?filter[role]=auditor')
        ->assertOk()
        ->json('items');

    expect($items)->toHaveCount(1);
    expect($items[0]['name'])->toBe('In Role');
});

test('the admin create form applies the same password rule as the users own change', function () {
    // Password::defaults() is min(8) outside production; the point is that
    // both paths resolve the same rule rather than the admin path being weaker.
    $this->actingAs($this->actor)
        ->post(route('backoffice.setting.user.store'), [
            'name' => 'Too Short',
            'email' => 'short@example.com',
            'password' => 'short',
        ])
        ->assertSessionHasErrors('password');

    expect(User::where('email', 'short@example.com')->exists())->toBeFalse();
});

test('the admin password rule is the configured default, not a hardcoded min', function () {
    $rules = (new UserRequest)->rules();
    $selfService = (new UpdatePasswordRequest)->rules();

    $adminRule = collect($rules['password'])->first(fn ($r) => $r instanceof Password);
    $ownRule = collect($selfService['password'])->first(fn ($r) => $r instanceof Password);

    expect($adminRule)->not->toBeNull();
    expect($adminRule == $ownRule)->toBeTrue();
});
