<?php

namespace App\Service\Auth;

use App\Contract\Auth\TwoFactorContract;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService implements TwoFactorContract
{
    public const RECOVERY_CODE_COUNT = 8;

    public function __construct(private readonly Google2FA $engine) {}

    /**
     * Confirmed: the user proved they can generate codes. Only a confirmed
     * secret is ever demanded at login.
     */
    public function isEnabled(User $user): bool
    {
        return ! is_null($user->two_factor_secret) && ! is_null($user->two_factor_confirmed_at);
    }

    /**
     * A secret exists but has not been confirmed yet.
     */
    public function isPending(User $user): bool
    {
        return ! is_null($user->two_factor_secret) && is_null($user->two_factor_confirmed_at);
    }

    public function enable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => Crypt::encryptString($this->engine->generateSecretKey()),
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($this->newRecoveryCodes())),
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    public function confirm(User $user, string $code): bool
    {
        if (! $this->verifyCode($user, $code)) {
            return false;
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return true;
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    public function secretKey(User $user): string
    {
        return Crypt::decryptString($user->two_factor_secret);
    }

    public function qrCodeSvg(User $user): string
    {
        $url = $this->engine->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $this->secretKey($user),
        );

        $writer = new Writer(
            new ImageRenderer(new RendererStyle(192, 0), new SvgImageBackEnd)
        );

        return $writer->writeString($url);
    }

    /**
     * @return array<int, string>
     */
    public function recoveryCodes(User $user): array
    {
        if (is_null($user->two_factor_recovery_codes)) {
            return [];
        }

        return json_decode(Crypt::decryptString($user->two_factor_recovery_codes), true) ?? [];
    }

    public function regenerateRecoveryCodes(User $user): void
    {
        $user->forceFill([
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($this->newRecoveryCodes())),
        ])->save();
    }

    public function verifyCode(User $user, string $code): bool
    {
        if (is_null($user->two_factor_secret)) {
            return false;
        }

        return (bool) $this->engine->verifyKey($this->secretKey($user), $code);
    }

    /**
     * Recovery codes are single use: a matching code is removed before this
     * returns, so a replayed code fails.
     */
    public function consumeRecoveryCode(User $user, string $code): bool
    {
        $codes = $this->recoveryCodes($user);

        $match = collect($codes)->first(fn (string $stored) => hash_equals($stored, $code));

        if (is_null($match)) {
            return false;
        }

        $remaining = array_values(array_filter($codes, fn (string $stored) => $stored !== $match));

        $user->forceFill([
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($remaining)),
        ])->save();

        return true;
    }

    /**
     * @return array<int, string>
     */
    private function newRecoveryCodes(): array
    {
        return collect()
            ->times(self::RECOVERY_CODE_COUNT, fn () => Str::random(10).'-'.Str::random(10))
            ->all();
    }
}
