<?php

declare(strict_types=1);

namespace App\Observers;

use App\Support\Cache\CacheInvalidator;
use Illuminate\Database\Eloquent\Model;

final class CacheInvalidationObserver
{
    public function saved(Model $model): void
    {
        CacheInvalidator::forModel($model);
    }

    public function deleted(Model $model): void
    {
        CacheInvalidator::forModel($model);
    }
}
