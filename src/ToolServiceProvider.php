<?php

namespace Elsed115\ResourceDrive;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Laravel\Nova\Nova;
use Laravel\Nova\Events\ServingNova;

class ToolServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Nova::serving(fn(ServingNova $event) => [
            Nova::script('resource-drive', __DIR__.'/../dist/js/tool.js'),
            Nova::style ('resource-drive', __DIR__.'/../dist/css/tool.css'),
        ]);

        if (! $this->app->routesAreCached()) {
            Route::middleware('nova')
                 ->prefix('nova-vendor/resource-drive')
                 ->group(__DIR__.'/../routes/api.php');
        }
    }
}
