<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class AdminDashboardCache
{
    public static function flush(?int $companyId = null): void
    {
        Cache::forget('dashboard:admin');
        Cache::forget('files:companies_with_storage');

        if ($companyId !== null) {
            Cache::forget('dashboard:company:'.$companyId);
        }
    }
}
