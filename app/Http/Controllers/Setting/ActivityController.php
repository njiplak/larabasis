<?php

namespace App\Http\Controllers\Setting;

use App\Contract\Setting\ActivityContract;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * The audit trail is read-only: entries are written by BaseService and are
 * never editable from the UI.
 */
class ActivityController extends Controller
{
    protected ActivityContract $service;

    public function __construct(ActivityContract $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return Inertia::render('setting/activity/index');
    }

    public function fetch()
    {
        $data = $this->service->all(
            allowedFilters: [
                AllowedFilter::exact('event'),
                AllowedFilter::exact('subject_type'),
                AllowedFilter::callback('search', fn ($query, $value) => $query->where(
                    fn ($q) => $q->where('description', 'like', "%{$value}%")
                        ->orWhere('subject_type', 'like', "%{$value}%")
                        ->orWhere('event', 'like', "%{$value}%")
                )),
            ],
            allowedSorts: ['id', 'event', 'subject_type', 'created_at'],
            withPaginate: true,
            orderColumn: 'id',
            orderPosition: 'desc',
            perPage: request()->get('per_page', 10),
        );

        return response()->json($data);
    }
}
