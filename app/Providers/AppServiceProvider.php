<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    // No custom service bindings are needed.
    public function register(): void
    {
        //
    }

    // Render Laravel's paginator views using Bootstrap 4 markup.
    public function boot(): void
    {
        Paginator::useBootstrapFour();
    }
}
