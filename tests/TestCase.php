<?php

namespace Contoweb\PrivacyRetention\Tests;

use Contoweb\PrivacyRetention\PrivacyRetentionServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamp('anonymized_at')->nullable();
            $table->timestamps();
        });

        Schema::create('masked_users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamp('masked_at')->nullable();
            $table->timestamps();
        });
    }

    protected function getPackageProviders($app): array
    {
        return [PrivacyRetentionServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
