<?php

namespace App\Providers;

use App\Models\Review;
use App\Models\User;
use App\Observers\ReviewObserver;
use App\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Register observers
        Review::observe(ReviewObserver::class);
        User::observe(UserObserver::class);

        // Admin portal hardening (laravel-admin registers its middleware in register())
        $router = $this->app['router'];
        $router->aliasMiddleware('admin.log', \App\Http\Middleware\AdminLogOperation::class);
        $router->aliasMiddleware('admin.staff', \App\Http\Middleware\AdminStaffOnly::class);
        // First in the group: admin.permission exits with its own page before
        // later middleware runs, so non-staff would never be signed out
        $router->prependMiddlewareToGroup('admin', 'admin.staff');
    }
}
