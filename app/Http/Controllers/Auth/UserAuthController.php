<?php

namespace App\Http\Controllers\Auth;

use App\Contract\Auth\UserAuthContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Utils\WebResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Throwable;

class UserAuthController extends Controller
{
    protected UserAuthContract $service;

    public function __construct(UserAuthContract $service)
    {
        $this->service = $service;
    }

    public function login()
    {
        return Inertia::render('auth/login');
    }

    public function attempt(LoginRequest $request)
    {
        $request->ensureIsNotRateLimited();

        $result = $this->service->login($request->validated());

        if ($result instanceof Throwable) {
            $request->hitRateLimiter();

            return WebResponse::response($result);
        }

        $request->clearRateLimiter();
        $request->session()->regenerate();

        return WebResponse::response($result, 'backoffice.index');
    }

    public function logout(Request $request)
    {
        $result = $this->service->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return WebResponse::response($result, 'login');
    }
}
