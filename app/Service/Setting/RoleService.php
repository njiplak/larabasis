<?php

namespace App\Service\Setting;

use App\Contract\Setting\RoleContract;
use App\Service\BaseService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RoleService extends BaseService implements RoleContract
{
    protected array $relation = ['permissions'];

    public function __construct(Role $model)
    {
        parent::__construct($model);
    }

    public function create($payloads)
    {
        try {
            $permissions = $payloads['permissions'] ?? [];
            unset($payloads['permissions']);

            DB::beginTransaction();
            $model = $this->model->create($payloads);
            $model->syncPermissions($permissions);

            $this->recordActivity('created', $model, [
                'attributes' => $this->loggableAttributes($model),
                'permissions' => $model->permissions->pluck('name')->all(),
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
            $permissions = $payloads['permissions'] ?? [];
            unset($payloads['permissions']);

            DB::beginTransaction();
            $model = $this->model->findOrFail($id);

            $before = $this->loggableAttributes($model);
            $permissionsBefore = $model->permissions->pluck('name')->all();

            $model->update($payloads);
            $model->syncPermissions($permissions);

            $changed = array_keys($model->getChanges());

            // Who can do what is the highest-value thing in the audit trail:
            // always record the permission set, changed or not.
            $this->recordActivity('updated', $model, [
                'old' => array_intersect_key($before, array_flip($changed))
                    + ['permissions' => $permissionsBefore],
                'attributes' => $this->loggableAttributes($model, $changed)
                    + ['permissions' => $model->fresh('permissions')->permissions->pluck('name')->all()],
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
}
