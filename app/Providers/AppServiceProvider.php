<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Note: there is deliberately no Gate::before() bypass for Super Admin.
     * The Super Admin role is seeded with every permission (RolePermissionSeeder),
     * which satisfies ordinary permission checks. A blanket Gate::before bypass
     * would also skip business-rule checks inside policies (e.g. "system roles
     * can't be deleted") for Super Admin, which is not what we want.
     */
    public function boot(): void
    {
        //
    }
}
