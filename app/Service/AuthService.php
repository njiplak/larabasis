<?php

namespace App\Service;

use App\Contract\AuthContract;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService implements AuthContract
{
    protected string $username = 'email';

    protected ?string $guard = null;

    protected ?string $guardForeignKey = null;

    protected Model $model;

    /**
     * Repositories constructor.
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function build(): Model
    {
        return $this->model;
    }

    /**
     * Get user id by guard name.
     */
    public function userID(): int
    {
        return Auth::guard($this->guard)->id();
    }

    /**
     * Check credentials without starting a session.
     *
     * One outcome for every failure, so the response cannot be used to
     * discover which email addresses are registered.
     */
    public function verifyCredentials(array $credentials): ?Model
    {
        $user = $this->model::query()
            ->where($this->username, $credentials[$this->username])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->getAuthPassword())) {
            return null;
        }

        return $user;
    }

    /**
     * Start the session for an already-verified user.
     */
    public function loginUser(Model $user, bool $remember = false): void
    {
        Auth::guard($this->guard)->login($user, $remember);
    }

    /**
     * Register new user.
     *
     * @return Model|Exception
     */
    public function register(array $payloads, $assignRole = [])
    {
        try {
            DB::beginTransaction();

            $user = $this->model->create($payloads);
            if ($assignRole) {
                $user->assignRole($assignRole);
            }

            DB::commit();

            return $user;
        } catch (Exception $exception) {
            DB::rollBack();

            return $exception;
        }
    }

    /**
     * Update user role and profile.
     *
     * @return Model|Exception
     */
    public function update($id, array $payloads, $assignRole = [])
    {
        try {
            DB::beginTransaction();

            $user = $this->model->findOrFail($id);
            $user->update($payloads);
            if ($assignRole) {
                $user->syncRoles($assignRole);
            }

            DB::commit();

            return $user->fresh();
        } catch (Exception $exception) {
            DB::rollBack();

            return $exception;
        }
    }

    /**
     * Log the current user out.
     *
     * @return Exception|true
     */
    public function logout(): Exception|bool
    {
        try {
            Auth::guard($this->guard)->logout();

            return true;
        } catch (Exception $exception) {
            return $exception;
        }
    }
}
