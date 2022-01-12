<?php

namespace k90mirzaei\Media;

use Illuminate\Support\ServiceProvider;
use k90mirzaei\Media\Console\CleanCommand;
use k90mirzaei\Media\Support\Generators\WidthsGenerator;
use k90mirzaei\Media\Support\Generators\UrlGenerator;

class BaseServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerPublished();
    }

    public function register()
    {
        $this->app->bind('UrlGenerator', function () {
            return new UrlGenerator();
        });

        $this->app->bind('WidthsGenerator', function () {
            return new WidthsGenerator();
        });

        $this->registerCommands();
    }

    protected function registerPublished()
    {
        $this->publishes([
            __DIR__.'/../config/media.php' => config_path('media.php'),
        ], 'media-config');

        if (! class_exists('CreateMediaTable')) {
            $this->publishes([
                __DIR__.'/../database/migrations/create_media_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_media_table.php'),
            ], 'media-migrations');
        }
    }

    protected function registerCommands()
    {
        $this->app->bind('command.media:clean', CleanCommand::class);

        $this->commands([
            'command.media:clean',
        ]);
    }
}