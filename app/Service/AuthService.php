<?php

namespace App\Service;

use App\Contract\AuthContract;
use App\Exceptions\UserMessageException;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
     * Log a user in.
     *
     * A single failure message for every cause, so the response cannot be used
     * to discover which email addresses are registered.
     *
     * @return true|UserMessageException
     */
    public function login(array $credentials)
    {
        $remember = (bool) ($credentials['remember'] ?? false);

        $attempt = [
            $this->username => $credentials[$this->username],
            'password' => $credentials['password'],
        ];

        if (! Auth::guard($this->guard)->attempt($attempt, $remember)) {
            return new UserMessageException(__('auth.failed'));
        }

        return true;
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
