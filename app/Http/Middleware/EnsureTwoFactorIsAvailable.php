<?php

namespace App\Http\Middleware;

use App\Contract\Auth\TwoFactorContract;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Two-factor auth is opt-in per project. When it is switched off its routes
 * stay registered — Wayfinder builds the front-end route helpers from the
 * route table — but they behave as if they do not exist.
 */
class EnsureTwoFactorIsAvailable
{
    public function __construct(protected TwoFactorContract $twoFactor) {}

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($this->twoFactor->isAvailable(), 404);

        return $next($request);
    }
}
