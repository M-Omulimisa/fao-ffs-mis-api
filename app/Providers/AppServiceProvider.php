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
    }
}
