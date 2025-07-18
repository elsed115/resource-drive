<?php
namespace Elsed115\ResourceDrive;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Laravel\Nova\Nova;
use Laravel\Nova\Events\ServingNova;

class ToolServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Log::info('ResourceDrive Tool: Booting Service Provider.');

        $this->app->booted(function () {
            $this->routes();
            Log::info('ResourceDrive Tool: Routes registered.');
        });

        Nova::serving(function (ServingNova $event) {
            Log::info('ResourceDrive Tool: ServingNova event caught.');
            Nova::script('resource-drive', __DIR__.'/../dist/js/tool.js');
            Nova::style('resource-drive', __DIR__.'/../dist/css/tool.css');
            Log::info('ResourceDrive Tool: Assets registered.');
        });
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
                ->prefix('nova-vendor/resource-drive')
                ->group(__DIR__.'/../routes/api.php');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Log::info('ResourceDrive Tool: Registering Service Provider.');
    }
}
