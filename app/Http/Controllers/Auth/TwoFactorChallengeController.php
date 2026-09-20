<?php

namespace App\Http\Controllers\Auth;

use App\Contract\Auth\TwoFactorContract;
use App\Contract\Auth\UserAuthContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\TwoFactorChallengeRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TwoFactorChallengeController extends Controller
{
    public function __construct(
        protected UserAuthContract $service,
        protected TwoFactorContract $twoFactor,
    ) {}

    public function show(Request $request)
    {
        if (! $request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        return Inertia::render('auth/two-factor-challenge');
    }

    public function store(TwoFactorChallengeRequest $request)
    {
        $user = $this->challengedUser($request);

        if (is_null($user)) {
            return redirect()->route('login');
        }

        $payload = $request->validated();
        $recoveryCode = $payload['recovery_code'] ?? null;

        // The form always posts both fields; only a non-empty one counts.
        $usingRecoveryCode = filled($recoveryCode);

        $passed = $usingRecoveryCode
            ? $this->twoFactor->consumeRecoveryCode($user, $recoveryCode)
            : $this->twoFactor->verifyCode($user, $payload['code'] ?? '');

        if (! $passed) {
            throw ValidationException::withMessages([
                $usingRecoveryCode ? 'recovery_code' : 'code' => __('The provided code was invalid.'),
            ]);
        }

        $remember = (bool) $request->session()->get('login.remember', false);

        $request->session()->forget(['login.id', 'login.remember']);
        $this->service->loginUser($user, $remember);
        $request->session()->regenerate();

        return redirect()->route('backoffice.index');
    }

    private function challengedUser(Request $request): ?User
    {
        $id = $request->session()->get('login.id');

        return $id ? User::find($id) : null;
    }
}
