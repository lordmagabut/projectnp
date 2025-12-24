<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Perusahaan;

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
        // Share first company (if any) with the sidebar view for logo/name display
        View::composer('layout.sidebar', function ($view) {
            $view->with('company', Perusahaan::first());
        });
    }
}
