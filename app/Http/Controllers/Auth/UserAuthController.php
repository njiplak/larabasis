<?php

namespace App\Http\Controllers\Auth;

use App\Contract\Auth\TwoFactorContract;
use App\Contract\Auth\UserAuthContract;
use App\Exceptions\UserMessageException;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Utils\WebResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserAuthController extends Controller
{
    public function __construct(
        protected UserAuthContract $service,
        protected TwoFactorContract $twoFactor,
    ) {}

    public function login(Request $request)
    {
        return Inertia::render('auth/login', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function attempt(LoginRequest $request)
    {
        $request->ensureIsNotRateLimited();

        $user = $this->service->verifyCredentials($request->validated());

        if (is_null($user)) {
            $request->hitRateLimiter();

            return WebResponse::response(new UserMessageException(__('auth.failed')));
        }

        $request->clearRateLimiter();

        // Credentials are correct but the session does not start yet: the
        // second factor is still owed.
        if ($this->twoFactor->isEnabled($user)) {
            $request->session()->put('login.id', $user->getKey());
            $request->session()->put('login.remember', $request->boolean('remember'));

            return WebResponse::response(true, 'two-factor.login');
        }

        $this->service->loginUser($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return WebResponse::response(true, 'backoffice.index');
    }

    public function logout(Request $request)
    {
        $result = $this->service->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return WebResponse::response($result, 'login');
    }
}
