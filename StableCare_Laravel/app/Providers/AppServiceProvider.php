<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.stablecare');

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
