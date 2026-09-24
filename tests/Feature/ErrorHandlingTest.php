<?php

use App\Exceptions\UserMessageException;
use App\Utils\WebResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

function respondWith(Throwable $e): string
{
    Route::post('/__test/web-response', fn () => WebResponse::response($e))->middleware('web');

    return test()->post('/__test/web-response')->getSession()->get('errors')->first('errors');
}

test('an unexpected exception is hidden behind a generic message', function () {
    Log::spy();

    $message = respondWith(new RuntimeException('SQLSTATE[23000]: users.email UNIQUE constraint'));

    expect($message)
        ->toBe(WebResponse::GENERIC_ERROR)
        ->not->toContain('SQLSTATE')
        ->not->toContain('users.email');
});

test('an unexpected exception is still reported to the log', function () {
    Log::spy();

    respondWith(new RuntimeException('boom'));

    Log::shouldHaveReceived('error')->once();
});

test('a deliberate user-facing message is shown verbatim', function () {
    $message = respondWith(new UserMessageException('That email is already in use.'));

    expect($message)->toBe('That email is already in use.');
});

test('json responses do not carry the exception object', function () {
    Log::spy();

    Route::get('/__test/web-response-json', fn () => WebResponse::json(new RuntimeException('SQLSTATE[23000]')))
        ->middleware('web');

    $response = $this->getJson('/__test/web-response-json')->assertStatus(400);

    expect($response->json())
        ->toBe(['message' => WebResponse::GENERIC_ERROR]);
});
