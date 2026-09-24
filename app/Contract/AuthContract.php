<?php

namespace App\Contract;

use Illuminate\Database\Eloquent\Model;

interface AuthContract
{
    /**
     * Check credentials without starting a session.
     * Returns null for any failure, so callers cannot tell the causes apart.
     */
    public function verifyCredentials(array $credentials): ?Model;

    /**
     * Start the session for an already-verified user.
     */
    public function loginUser(Model $user, bool $remember = false): void;

    public function register(array $payloads, $assignRole = []);

    public function logout();

    public function update($id, array $payloads, $assignRole = []);
}
