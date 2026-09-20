<?php

namespace App\Http\Controllers\Setting;

use App\Contract\Setting\UserContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkDeleteRequest;
use App\Http\Requests\UserRequest;
use App\Utils\WebResponse;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\QueryBuilder\AllowedFilter;

class UserController extends Controller
{
    protected UserContract $service;

    public function __construct(UserContract $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return Inertia::render('setting/user/index');
    }

    public function fetch()
    {
        $data = $this->service->all(
            allowedFilters: [
                AllowedFilter::partial('name'),
                AllowedFilter::partial('email'),
                AllowedFilter::callback('search', fn ($query, $value) => $query->where(
                    fn ($q) => $q->where('name', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%")
                )),
                AllowedFilter::callback('role', fn ($query, $value) => $query->whereHas(
                    'roles',
                    fn ($q) => $q->where('name', $value)
                )),
                AllowedFilter::trashed(),
            ],
            allowedSorts: ['id', 'name', 'email', 'created_at', 'updated_at'],
            withPaginate: true,
            perPage: request()->get('per_page', 10),
        );

        return response()->json($data);
    }

    public function create()
    {
        return Inertia::render('setting/user/form', [
            'roles' => $this->getRoles(),
        ]);
    }

    public function store(UserRequest $request)
    {
        $data = $this->service->create($request->validated());

        return WebResponse::response($data, 'backoffice.setting.user.index');
    }

    public function show($id)
    {
        return Inertia::render('setting/user/form', [
            'user' => $this->service->find($id),
            'roles' => $this->getRoles(),
        ]);
    }

    public function update(UserRequest $request, $id)
    {
        $data = $this->service->update($id, $request->validated());

        return WebResponse::response($data, 'backoffice.setting.user.index');
    }

    public function destroy($id)
    {
        $data = $this->service->destroy($id);

        return WebResponse::response($data, 'backoffice.setting.user.index');
    }

    public function restore($id)
    {
        $data = $this->service->restore($id);

        return WebResponse::response($data, 'backoffice.setting.user.index');
    }

    public function destroy_bulk(BulkDeleteRequest $request)
    {
        $data = $this->service->bulkDeleteByIds($request->ids());

        return WebResponse::response($data, 'backoffice.setting.user.index');
    }

    private function getRoles(): array
    {
        return Role::all(['id', 'name'])->toArray();
    }
}
