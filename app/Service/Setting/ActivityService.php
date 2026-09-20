<?php

namespace App\Service\Setting;

use App\Contract\Setting\ActivityContract;
use App\Service\BaseService;
use Spatie\Activitylog\Models\Activity;

class ActivityService extends BaseService implements ActivityContract
{
    protected array $relation = ['causer'];

    public function __construct(Activity $model)
    {
        parent::__construct($model);
    }
}
