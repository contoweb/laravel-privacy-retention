<?php

namespace Contoweb\PrivacyRetention;

use Contoweb\PrivacyRetention\Console\MakeRetentionMigrationCommand;
use Contoweb\PrivacyRetention\Console\RetentionAnonymizeCommand;
use Illuminate\Support\ServiceProvider;

class PrivacyRetentionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/privacy-retention.php', 'privacy-retention');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/privacy-retention.php' => config_path('privacy-retention.php'),
            ], 'privacy-retention-config');

            $this->commands([
                RetentionAnonymizeCommand::class,
                MakeRetentionMigrationCommand::class,
            ]);
        }
    }
}
