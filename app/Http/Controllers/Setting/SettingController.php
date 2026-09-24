<?php

namespace App\Http\Controllers\Setting;

use App\Contract\Setting\SettingContract;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkDeleteRequest;
use App\Http\Requests\SettingRequest;
use App\Utils\WebResponse;
use Inertia\Inertia;
use Spatie\QueryBuilder\AllowedFilter;

class SettingController extends Controller
{
    protected SettingContract $service;

    public function __construct(SettingContract $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return Inertia::render(component: 'setting/setting/index');
    }

    public function fetch()
    {
        $data = $this->service->all(
            allowedFilters: [
                AllowedFilter::partial('key'),
                AllowedFilter::partial('value'),
                AllowedFilter::callback('search', fn ($query, $value) => $query->where(
                    fn ($q) => $q->where('key', 'like', "%{$value}%")
                        ->orWhere('value', 'like', "%{$value}%")
                )),
                AllowedFilter::trashed(),
            ],
            allowedSorts: ['id', 'key', 'value', 'created_at', 'updated_at'],
            withPaginate: true,
            perPage: request()->get('per_page', 10)
        );

        return response()->json($data);
    }

    public function create()
    {
        return Inertia::render('setting/setting/form');
    }

    public function store(SettingRequest $request)
    {
        $data = $this->service->create($request->validated());

        return WebResponse::response($data, 'backoffice.setting.setting.index');
    }

    public function show($id)
    {
        return Inertia::render('setting/setting/form', [
            'setting' => $this->service->find($id),
        ]);
    }

    public function update(SettingRequest $request, $id)
    {
        $data = $this->service->update($id, $request->validated());

        return WebResponse::response($data, 'backoffice.setting.setting.index');
    }

    public function destroy($id)
    {
        $data = $this->service->destroy($id);

        return WebResponse::response($data, 'backoffice.setting.setting.index');
    }

    public function restore($id)
    {
        $data = $this->service->restore($id);

        return WebResponse::response($data, 'backoffice.setting.setting.index');
    }

    public function destroy_bulk(BulkDeleteRequest $request)
    {
        $data = $this->service->bulkDeleteByIds($request->ids());

        return WebResponse::response($data, 'backoffice.setting.setting.index');
    }
}
