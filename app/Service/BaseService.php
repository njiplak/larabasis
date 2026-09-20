<?php

namespace App\Service;

use App\Contract\BaseContract;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

class BaseService implements BaseContract
{
    protected array $relation = [];

    protected ?string $guard = null;

    protected ?string $guardForeignKey = null;

    protected array $fileKeys = [];

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
     * Get all items from resource.
     *
     * Read failures (an unknown filter, an invalid sort) are left to bubble up
     * so the request fails with a real status code instead of a 200 body.
     *
     * @return array|Collection
     */
    public function all(
        $allowedFilters,
        $allowedSorts,
        ?bool $withPaginate = null,
        array $relation = [],
        int $perPage = 10,
        string $orderColumn = 'id',
        string $orderPosition = 'asc',
        array $conditions = [],
    ) {
        $model = QueryBuilder::for($this->model::class)
            ->allowedFilters($allowedFilters)
            ->allowedSorts($allowedSorts)
            ->with(empty($relation) ? $this->relation : $relation)
            ->where($conditions)
            ->when(! is_null($this->guardForeignKey), function ($query) {
                $query->where($this->guardForeignKey, $this->userID());
            })
            ->orderBy($orderColumn, $orderPosition);

        if (is_null($withPaginate)) {
            $withPaginate = config('service-contract.default_paginated');
        }
        if (! $withPaginate) {
            return $model->get();
        }

        return $this->paginated($model->paginate(request()->get('per_page', $perPage)));
    }

    /**
     * Find item by id from resource.
     *
     * A missing record throws ModelNotFoundException so the request 404s
     * instead of rendering a page with an exception in its props.
     *
     * @param  mixed  $id
     * @return Model
     *
     * @throws ModelNotFoundException
     */
    public function find($id, array $relation = [])
    {
        return $this->model
            ->with(empty($relation) ? $this->relation : $relation)
            ->when(! is_null($this->guardForeignKey), function ($query) {
                $query->where($this->guardForeignKey, $this->userID());
            })
            ->findOrFail($id);
    }

    /**
     * Create new item to resource.
     *
     * @return Model|Exception
     */
    public function create($payloads)
    {
        try {
            if (! is_null($this->guardForeignKey)) {
                $payloads[$this->guardForeignKey] = $this->userID();
            }

            DB::beginTransaction();
            $model = $this->model->create($payloads);

            $this->syncMedia($model);

            DB::commit();

            return $model->fresh();
        } catch (Exception $e) {
            DB::rollBack();

            return $e;
        }
    }

    public function insert($payloads)
    {
        try {
            DB::beginTransaction();
            $this->model->insert($payloads);
            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            return $e;
        }
    }

    /**
     * Update item from resource.
     *
     * @param  mixed  $id
     * @param  mixed  $payloads
     * @return Model|Exception
     *
     * @throws ModelNotFoundException
     */
    public function update($id, $payloads)
    {
        try {
            if (! is_null($this->guardForeignKey)) {
                $payloads[$this->guardForeignKey] = $this->userID();
            }

            foreach ($this->fileKeys as $fileKey) {
                unset($payloads[$fileKey]);
            }

            DB::beginTransaction();
            $model = $this->model
                ->when(! is_null($this->guardForeignKey), function ($query) {
                    $query->where($this->guardForeignKey, $this->userID());
                })
                ->findOrFail($id);
            $model->update($payloads);

            $this->syncMedia($model);
            DB::commit();

            return $model->fresh();
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();

            return $e;
        }
    }

    /**
     * Destroy item from resource.
     *
     * @return bool|Exception
     *
     * @throws ModelNotFoundException
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $deleted = $this->model
                ->when(! is_null($this->guardForeignKey), function ($query) {
                    $query->where($this->guardForeignKey, $this->userID());
                })
                ->findOrFail($id)
                ->delete();
            DB::commit();

            return $deleted;
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();

            return $e;
        }
    }

    /**
     * Get items with certain conditions.
     *
     * @param  mixed  $conditions
     * @param  mixed  $allowedFilters
     * @param  mixed  $allowedSorts
     * @return array|Collection
     */
    public function getWithCondition(
        $conditions,
        $allowedFilters,
        $allowedSorts,
        ?bool $withPaginate = null,
        $relation = [],
        string $orderColumn = 'id',
        string $orderPosition = 'asc',
        int $perPage = 10,
    ) {
        $model = QueryBuilder::for($this->model::class);

        if (is_array($conditions) && isset($conditions[0]) && is_array($conditions[0])) {
            $model->where($conditions);
        } else {
            $model->where(...$conditions);
        }

        $model->allowedFilters($allowedFilters)
            ->allowedSorts($allowedSorts)
            ->with(empty($relation) ? $this->relation : $relation)
            ->when(! is_null($this->guardForeignKey), function ($query) {
                $query->where($this->guardForeignKey, $this->userID());
            })
            ->orderBy($orderColumn, $orderPosition);

        if (is_null($withPaginate)) {
            $withPaginate = config('service-contract.default_paginated');
        }
        if (! $withPaginate) {
            return $model->get();
        }

        return $this->paginated($model->paginate(request()->get('per_page', $perPage)));
    }

    /**
     * Update items with certain conditions.
     *
     * @return Model|null|Exception
     */
    public function updateWithCondition($conditions, $payloads)
    {
        try {
            DB::beginTransaction();
            $this->model->where($conditions)->update($payloads);
            DB::commit();

            return $this->model->where($conditions)->first();
        } catch (Exception $e) {
            DB::rollBack();

            return $e;
        }
    }

    /**
     * Bulk delete items based on an array of IDs.
     *
     * @return int|Exception number of deleted rows
     */
    public function bulkDeleteByIds(array $ids)
    {
        try {
            DB::beginTransaction();

            $deleted = $this->model
                ->when(! is_null($this->guardForeignKey), function ($query) {
                    $query->where($this->guardForeignKey, $this->userID());
                })
                ->whereIn('id', $ids)
                ->get()
                ->each
                ->delete()
                ->count();

            DB::commit();

            return $deleted;
        } catch (Exception $e) {
            DB::rollBack();

            return $e;
        }
    }

    /**
     * Bulk update items based on an array of IDs.
     *
     * @return int|Exception number of updated rows
     */
    public function bulkUpdate(array $ids, $params)
    {
        try {
            DB::beginTransaction();

            $updated = $this->model
                ->when(! is_null($this->guardForeignKey), function ($query) {
                    $query->where($this->guardForeignKey, $this->userID());
                })
                ->whereIn('id', $ids)
                ->update($params);

            DB::commit();

            return $updated;
        } catch (Exception $e) {
            DB::rollBack();

            return $e;
        }
    }

    /**
     * Attach uploaded files for every configured media key.
     */
    protected function syncMedia(Model $model): void
    {
        foreach ($this->fileKeys as $fileKey) {
            $model->addMultipleMediaFromRequest([$fileKey])
                ->each(function ($file) use ($fileKey) {
                    $file->toMediaCollection($fileKey);
                });
        }
    }

    /**
     * Shape a paginator into the envelope the front-end table expects.
     */
    protected function paginated(LengthAwarePaginator $result): array
    {
        $result->appends(request()->query());

        $startOrderNo = ($result->currentPage() - 1) * $result->perPage() + 1;

        $items = collect($result->items())->map(function ($item, $index) use ($startOrderNo) {
            $item->order_no = $startOrderNo + $index;

            return $item;
        })->all();

        return [
            'items' => $items,
            'prev_page' => $result->currentPage() > 1 ? $result->currentPage() - 1 : null,
            'current_page' => $result->currentPage(),
            'next_page' => $result->hasMorePages() ? $result->currentPage() + 1 : null,
            'total_page' => $result->lastPage(),
            'per_page' => $result->perPage(),
        ];
    }
}
