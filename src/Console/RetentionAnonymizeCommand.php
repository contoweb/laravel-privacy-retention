<?php

namespace Contoweb\PrivacyRetention\Console;

use Contoweb\PrivacyRetention\Concerns\HasRetentionPolicy;
use Contoweb\PrivacyRetention\Contracts\Anonymizable;
use Illuminate\Console\Command;
use RuntimeException;

class RetentionAnonymizeCommand extends Command
{
    protected $signature = 'retention:anonymize';

    protected $description = 'Anonymize personal data on all configured Anonymizable models past their retention window.';

    public function handle(): int
    {
        $models = (array) config('privacy-retention.models', []);

        if ($models === []) {
            $this->warn('No models configured in config/privacy-retention.php.');

            return self::SUCCESS;
        }

        foreach ($models as $model) {
            $this->guardContract($model);

            $count = 0;

            $model::anonymizableQuery()
                ->notAnonymized()
                ->chunkById(1000, function ($records) use (&$count): void {
                    foreach ($records as $record) {
                        $record->anonymize();
                        $count++;
                    }
                });

            $this->info(sprintf('Anonymized %d %s record(s).', $count, $model));
        }

        return self::SUCCESS;
    }

    private function guardContract(string $model): void
    {
        if (! is_subclass_of($model, Anonymizable::class)) {
            throw new RuntimeException(sprintf('%s must implement %s.', $model, Anonymizable::class));
        }

        if (! in_array(HasRetentionPolicy::class, class_uses_recursive($model), true)) {
            throw new RuntimeException(sprintf('%s must use the %s trait.', $model, HasRetentionPolicy::class));
        }
    }
}
