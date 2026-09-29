<?php

namespace Contoweb\PrivacyRetention\Tests;

use Contoweb\PrivacyRetention\Tests\Fixtures\TestUser;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class MakeRetentionMigrationCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        File::deleteDirectory($this->app->databasePath('migrations'));
        File::ensureDirectoryExists($this->app->databasePath('migrations'));
    }

    public function test_creates_migration_from_table_option(): void
    {
        Artisan::call('retention:make-migration', ['--table' => 'widgets']);

        $files = File::files($this->app->databasePath('migrations'));
        $this->assertCount(1, $files);

        $contents = File::get($files[0]->getPathname());
        $this->assertStringContainsString("Schema::table('widgets'", $contents);
        $this->assertStringContainsString("timestamp('anonymized_at')", $contents);
    }

    public function test_creates_migration_from_model_option(): void
    {
        Artisan::call('retention:make-migration', ['--model' => TestUser::class]);

        $files = File::files($this->app->databasePath('migrations'));
        $contents = File::get($files[0]->getPathname());

        $this->assertStringContainsString("Schema::table('test_users'", $contents);
    }

    public function test_respects_custom_column_option(): void
    {
        Artisan::call('retention:make-migration', [
            '--table'  => 'widgets',
            '--column' => 'masked_at',
        ]);

        $files = File::files($this->app->databasePath('migrations'));
        $contents = File::get($files[0]->getPathname());

        $this->assertStringContainsString("timestamp('masked_at')", $contents);
    }
}
