<?php

namespace App\Service\Setting;

use App\Contract\Auth\TwoFactorContract;
use App\Contract\Setting\UserContract;
use App\Exceptions\UserMessageException;
use App\Models\User;
use App\Service\BaseService;
use Database\Seeders\RbacSeeder;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UserService extends BaseService implements UserContract
{
    protected array $relation = ['roles'];

    public function __construct(User $model, protected TwoFactorContract $twoFactor)
    {
        parent::__construct($model);
    }

    /**
     * Clear a user's two-factor enrolment.
     *
     * Their secret and recovery codes are destroyed, not suspended, so the
     * only way back in is a fresh enrolment. Audited like any other change.
     *
     * @return bool|Exception
     *
     * @throws ModelNotFoundException
     */
    public function resetTwoFactor($id)
    {
        try {
            DB::beginTransaction();
            $user = $this->model->findOrFail($id);

            $wasEnrolled = ! is_null($user->two_factor_secret);

            $this->twoFactor->disable($user);
            $this->recordActivity('two-factor-reset', $user, [
                'was_enrolled' => $wasEnrolled,
            ]);

            DB::commit();

            return true;
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();

            return $e;
        }
    }

    public function create($payloads)
    {
        try {
            $role = $payloads['role'] ?? null;
            unset($payloads['role']);

            DB::beginTransaction();
            $model = $this->model->create($payloads);

            if ($role) {
                $model->syncRoles([$role]);
            }

            $this->recordActivity('created', $model, [
                'attributes' => $this->loggableAttributes($model),
                'role' => $model->getRoleNames()->all(),
            ]);

            DB::commit();

            return $model->fresh($this->relation);
        } catch (Exception $e) {
            DB::rollBack();

            return $e;
        }
    }

    public function update($id, $payloads)
    {
        try {
            $role = $payloads['role'] ?? null;
            unset($payloads['role']);

            // A blank password field means "leave it alone", not "clear it".
            if (empty($payloads['password'])) {
                unset($payloads['password']);
            }

            DB::beginTransaction();
            $model = $this->model->findOrFail($id);

            $before = $this->loggableAttributes($model);
            $rolesBefore = $model->getRoleNames()->all();

            $model->update($payloads);
            $model->syncRoles($role ? [$role] : []);

            $changed = array_keys($model->getChanges());

            $this->recordActivity('updated', $model, [
                'old' => array_intersect_key($before, array_flip($changed)) + ['role' => $rolesBefore],
                'attributes' => $this->loggableAttributes($model, $changed)
                    + ['role' => $model->getRoleNames()->all()],
            ]);

            DB::commit();

            return $model->fresh($this->relation);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();

            return $e;
        }
    }

    /**
     * @return bool|Exception
     */
    public function destroy($id)
    {
        if ($blocked = $this->blockedFromDeleting([$id])) {
            return $blocked;
        }

        return parent::destroy($id);
    }

    /**
     * @return int|Exception
     */
    public function bulkDeleteByIds(array $ids)
    {
        if ($blocked = $this->blockedFromDeleting($ids)) {
            return $blocked;
        }

        return parent::bulkDeleteByIds($ids);
    }

    /**
     * Two ways to lock everyone out of the console: delete yourself, or
     * delete the last account that can still administer it.
     */
    private function blockedFromDeleting(array $ids): ?UserMessageException
    {
        $ids = array_map('intval', $ids);

        if (in_array((int) auth()->id(), $ids, true)) {
            return new UserMessageException('You cannot delete your own account.');
        }

        // Nothing to protect if the role was never seeded.
        if (! Role::where('name', RbacSeeder::SUPER_ADMIN)->where('guard_name', 'web')->exists()) {
            return null;
        }

        $superAdminIds = User::role(RbacSeeder::SUPER_ADMIN)->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($superAdminIds === []) {
            return null;
        }

        $remaining = array_diff($superAdminIds, $ids);

        if ($remaining === []) {
            return new UserMessageException(
                'That would delete the last '.RbacSeeder::SUPER_ADMIN.'. Assign the role to another user first.'
            );
        }

        return null;
    }
}
