<?php

namespace App\Http\Controllers\Account;

use App\Contract\Auth\TwoFactorContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmTwoFactorRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TwoFactorController extends Controller
{
    public function __construct(protected TwoFactorContract $twoFactor) {}

    public function show(Request $request)
    {
        return Inertia::render('settings/two-factor', [
            'twoFactorEnabled' => $this->twoFactor->isEnabled($request->user()),
            'requiresConfirmation' => true,
        ]);
    }

    public function enable(Request $request)
    {
        $this->twoFactor->enable($request->user());

        return back();
    }

    public function confirm(ConfirmTwoFactorRequest $request)
    {
        $confirmed = $this->twoFactor->confirm($request->user(), $request->validated()['code']);

        if (! $confirmed) {
            // The setup modal reads errors.confirmTwoFactorAuthentication.code
            throw ValidationException::withMessages(['code' => __('The provided code was invalid.')])
                ->errorBag('confirmTwoFactorAuthentication');
        }

        return back();
    }

    public function disable(Request $request)
    {
        $this->twoFactor->disable($request->user());

        return back();
    }

    public function qrCode(Request $request): JsonResponse
    {
        $this->abortUnlessSetupStarted($request);

        return response()->json(['svg' => $this->twoFactor->qrCodeSvg($request->user())]);
    }

    public function secretKey(Request $request): JsonResponse
    {
        $this->abortUnlessSetupStarted($request);

        return response()->json(['secretKey' => $this->twoFactor->secretKey($request->user())]);
    }

    public function recoveryCodes(Request $request): JsonResponse
    {
        $this->abortUnlessSetupStarted($request);

        return response()->json($this->twoFactor->recoveryCodes($request->user()));
    }

    public function regenerateRecoveryCodes(Request $request)
    {
        $this->abortUnlessSetupStarted($request);

        $this->twoFactor->regenerateRecoveryCodes($request->user());

        return back();
    }

    /**
     * Never hand out a secret, QR code or recovery codes for a user who has
     * not started setting two-factor up.
     */
    private function abortUnlessSetupStarted(Request $request): void
    {
        $user = $request->user();

        abort_unless(
            $this->twoFactor->isEnabled($user) || $this->twoFactor->isPending($user),
            404
        );
    }
}
