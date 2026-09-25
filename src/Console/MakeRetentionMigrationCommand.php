<?php

namespace Contoweb\PrivacyRetention\Console;

use Illuminate\Console\Command;
use InvalidArgumentException;

class MakeRetentionMigrationCommand extends Command
{
    protected $signature = 'retention:make-migration
                            {--model= : Fully-qualified model class name}
                            {--table= : Table name (used when no model is given)}
                            {--column= : Override the timestamp column name}';

    protected $description = 'Generate a migration that adds the anonymized_at column to a table.';

    public function handle(): int
    {
        $table = $this->resolveTable();
        $column = (string) ($this->option('column') ?: $this->resolveColumn());

        $stub = file_get_contents(__DIR__.'/../../stubs/retention_migration.stub');
        $contents = str_replace(['{{ table }}', '{{ column }}'], [$table, $column], $stub);

        $path = $this->migrationPath($table, $column);
        file_put_contents($path, $contents);

        $this->info(sprintf('Created migration: %s', basename($path)));

        return self::SUCCESS;
    }

    private function resolveColumn(): string
    {
        $model = $this->option('model');

        if ($model && class_exists($model)) {
            $instance = new $model;

            if (method_exists($instance, 'getAnonymizedAtColumn')) {
                return $instance->getAnonymizedAtColumn();
            }
        }

        return 'anonymized_at';
    }

    private function resolveTable(): string
    {
        $model = $this->option('model');
        $table = $this->option('table');

        if ($model && $table) {
            throw new InvalidArgumentException('Pass either --model or --table, not both.');
        }

        if (! $model && ! $table) {
            throw new InvalidArgumentException('Either --model or --table is required.');
        }

        if ($model) {
            if (! class_exists($model)) {
                throw new InvalidArgumentException(sprintf('Model class %s not found.', $model));
            }

            return (new $model)->getTable();
        }

        return (string) $table;
    }

    private function migrationPath(string $table, string $column): string
    {
        $base = $this->laravel->databasePath('migrations');

        $timestamp = date('Y_m_d_His');
        $name = sprintf('add_%s_to_%s_table', $column, $table);

        return sprintf('%s/%s_%s.php', $base, $timestamp, $name);
    }
}
