<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
     */
    public function boot(): void
    {
         DB::connection('sqlsrv')->setReconnector(function ($connection) {
            $connection->disconnect();
            $connection->reconnect();
        });
        View::composer('*', function ($view) {
            $view->with('backUrl', url()->previous());
        });
       
    }

    //   public function boot(): void
    // {
    //     View::composer('*', function ($view) {
    //         $view->with('backUrl', url()->previous());
    //     });

    //     Log::info('APP BOOT TRACE', [
    //         'url' => request()->fullUrl(),
    //         'path' => request()->path(),
    //         'env' => app()->environment(),
    //         'key' => config('app.key'),
    //     ]);
    // }
}
