<?php

namespace App\Contract\Auth;

use App\Models\User;

interface TwoFactorContract
{
    /**
     * Whether this project offers two-factor auth at all.
     */
    public function isAvailable(): bool;

    public function isEnabled(User $user): bool;

    public function isPending(User $user): bool;

    public function enable(User $user): void;

    public function confirm(User $user, string $code): bool;

    public function disable(User $user): void;

    public function secretKey(User $user): string;

    public function qrCodeSvg(User $user): string;

    /**
     * @return array<int, string>
     */
    public function recoveryCodes(User $user): array;

    public function regenerateRecoveryCodes(User $user): void;

    public function verifyCode(User $user, string $code): bool;

    public function consumeRecoveryCode(User $user, string $code): bool;
}
