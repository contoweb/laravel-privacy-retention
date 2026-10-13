<?php

namespace Contoweb\PrivacyRetention;

use Contoweb\PrivacyRetention\Console\MakeRetentionMigrationCommand;
use Contoweb\PrivacyRetention\Console\RetentionAnonymizeCommand;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class PrivacyRetentionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/privacy-retention.php', 'privacy-retention');

        $this->app->bind(Anonymizer::class, function (Application $app) {
            return new Anonymizer(
                $app['config']->get('privacy-retention.anonymizer.mask_character'),
                $app['config']->get('privacy-retention.anonymizer.mask_length'),
                $app['config']->get('privacy-retention.anonymizer.email_domain'),
                $app['config']->get('privacy-retention.anonymizer.fixed_value'),
            );
        });

        $this->app->bind(RetentionAnonymizeCommand::class, function (Application $app) {
            return new RetentionAnonymizeCommand($app['config']->get('privacy-retention.models') ?? []);
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/privacy-retention.php' => $this->app->configPath('privacy-retention.php'),
            ], 'privacy-retention-config');

            $this->commands([
                RetentionAnonymizeCommand::class,
                MakeRetentionMigrationCommand::class,
            ]);
        }
    }
}
