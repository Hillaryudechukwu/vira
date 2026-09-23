<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Application-wide bindings belong here.
    }

    public function boot(): void
    {
        // Application-wide boot logic belongs here.
    }
}
