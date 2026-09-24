<?php

use App\Models\Setting;

beforeEach(function () {
    $this->actor = superAdmin();
});

test('a setting is created', function () {
    $this->actingAs($this->actor)
        ->post(route('backoffice.setting.setting.store'), ['key' => 'support_email', 'value' => 'help@example.com'])
        ->assertSessionHasNoErrors();

    expect(Setting::where('key', 'support_email')->first()->value)->toBe('help@example.com');
});

test('a setting value may be empty', function () {
    $this->actingAs($this->actor)
        ->post(route('backoffice.setting.setting.store'), ['key' => 'blank_key', 'value' => null])
        ->assertSessionHasNoErrors();

    expect(Setting::where('key', 'blank_key')->exists())->toBeTrue();
});

test('a duplicate key is rejected', function () {
    Setting::create(['key' => 'app_name', 'value' => 'Kawakib']);

    $this->actingAs($this->actor)
        ->post(route('backoffice.setting.setting.store'), ['key' => 'app_name', 'value' => 'Other'])
        ->assertSessionHasErrors('key');
});

test('a setting keeps its own key valid on update', function () {
    $setting = Setting::create(['key' => 'app_name', 'value' => 'Kawakib']);

    $this->actingAs($this->actor)
        ->put(route('backoffice.setting.setting.update', ['id' => $setting->id]), ['key' => 'app_name', 'value' => 'Renamed'])
        ->assertSessionHasNoErrors();

    expect($setting->fresh()->value)->toBe('Renamed');
});

test('the key is required', function () {
    $this->actingAs($this->actor)
        ->post(route('backoffice.setting.setting.store'), ['value' => 'orphan'])
        ->assertSessionHasErrors('key');
});
