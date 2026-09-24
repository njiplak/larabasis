<?php

namespace App\Http\Controllers\Setting;

use App\Contract\Setting\RoleContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkDeleteRequest;
use App\Http\Requests\RoleRequest;
use App\Utils\WebResponse;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\QueryBuilder\AllowedFilter;

class RoleController extends Controller
{
    protected RoleContract $service;

    public function __construct(RoleContract $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return Inertia::render(component: 'setting/role/index');
    }

    public function fetch()
    {
        $data = $this->service->all(
            allowedFilters: [
                AllowedFilter::partial('name'),
                AllowedFilter::callback('search', fn ($query, $value) => $query->where('name', 'like', "%{$value}%")),
            ],
            allowedSorts: ['id', 'name', 'created_at', 'updated_at'],
            withPaginate: true,
            perPage: request()->get('per_page', 10)
        );

        return response()->json($data);
    }

    public function create()
    {
        return Inertia::render('setting/role/form', [
            'permissions' => $this->getGroupedPermissions(),
        ]);
    }

    public function store(RoleRequest $request)
    {
        $data = $this->service->create($request->validated());

        return WebResponse::response($data, 'backoffice.setting.role.index');
    }

    public function show($id)
    {
        return Inertia::render('setting/role/form', [
            'role' => $this->service->find($id),
            'permissions' => $this->getGroupedPermissions(),
        ]);
    }

    public function update(RoleRequest $request, $id)
    {
        $data = $this->service->update($id, $request->validated());

        return WebResponse::response($data, 'backoffice.setting.role.index');
    }

    public function destroy($id)
    {
        $data = $this->service->destroy($id);

        return WebResponse::response($data, 'backoffice.setting.role.index');
    }

    public function destroy_bulk(BulkDeleteRequest $request)
    {
        $data = $this->service->bulkDeleteByIds($request->ids());

        return WebResponse::response($data, 'backoffice.setting.role.index');
    }

    private function getGroupedPermissions(): array
    {
        return Permission::all()
            ->groupBy(fn ($permission) => explode('.', $permission->name)[0])
            ->map(fn ($permissions, $module) => [
                'module' => $module,
                'permissions' => $permissions->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }
}
