<?php

namespace Elsed115\ResourceDrive;

use Laravel\Nova\Fields\Field;
use Illuminate\Support\Facades\Route;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;

class ResourceDrive extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'resource-drive';

    /**
     * Boot the field.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::registerRoutes();

        Nova::serving(function (ServingNova $event) {
            Nova::script('resource-drive', __DIR__.'/../dist/js/tool.js');
            Nova::style('resource-drive', __DIR__.'/../dist/css/tool.css');
        });
    }

    /**
     * Register the field's routes.
     *
     * @return void
     */
    public static function registerRoutes()
    {
        $appName = config('app.name');
        if (app()->routesAreCached() && file_exists(app()->getCachedRoutesPath()) && str_contains(file_get_contents(app()->getCachedRoutesPath()), 'nova-vendor/elsed115/resource-drive')) {
            return;
        }

        Route::middleware(['nova'])
            ->prefix('nova-vendor/elsed115/resource-drive')
            ->group(__DIR__.'/../routes/api.php');
    }
}
