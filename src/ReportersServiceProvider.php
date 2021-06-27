<?php
namespace Encore\Admin\Reporters;

use Illuminate\Support\ServiceProvider;
use Encore\Admin\Admin;
class ReportersServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot(Reporters $extension)
    {
        if (! Reporters::boot()) {
            return ;
        }

        if ($views = $extension->views()) {
            $this->loadViewsFrom($views, 'reporters');
        }

        if ($this->app->runningInConsole() && $assets = $extension->assets()) {
            $this->publishes(
                [$assets => public_path('vendor/laravel-admin-ext/reporters')],
                'reporters'
            );
        }

        if ($this->app->runningInConsole() && $migrations = $extension->migrations()) {
            $this->loadMigrationsFrom($migrations);
        }

        Admin::booting(function () {
            Admin::js('vendor/laravel-admin-ext/reporters/prism/prism.js');
            Admin::css('vendor/laravel-admin-ext/reporters/prism/prism.css');
        });

        $this->app->booted(function () {
            Reporters::routes(__DIR__.'/../routes/web.php');
        });
    }
}
