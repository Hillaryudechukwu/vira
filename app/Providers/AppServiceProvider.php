<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Application-wide bindings belong here.
    }

    public function boot(): void
    {
        // Compatible with shared MySQL/MariaDB installations that enforce
        // the legacy 1000-byte maximum index length under utf8mb4.
        Schema::defaultStringLength(191);
    }
}
