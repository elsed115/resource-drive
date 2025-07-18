<?php
namespace Elsed115\ResourceDrive;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;
use Illuminate\Support\ServiceProvider as BaseToolServiceProvider;

class ToolServiceProvider extends BaseToolServiceProvider
{
    public function boot()
    {

        Log::info('ResourceDrive Tool: Booting Service Provider.');
  
        Nova::serving(function (ServingNova $event) {
            Log::info('ResourceDrive Tool: ServingNova event caught.');
            Nova::script('resource-drive', __DIR__ . '/../dist/js/tool.js');
            Nova::style('resource-drive', __DIR__ . '/../dist/css/tool.css');
            Log::info('ResourceDrive Tool: Assets registered.');
        });

        $this->routes();
    }

    /**
     * Register the tool's routes.
     *
     * @return void
     */
    protected function routes()
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware(['nova'])
            ->prefix('nova-vendor/elsed115/resource-drive')
            ->group(__DIR__ . '/../routes/api.php');
    }
}
