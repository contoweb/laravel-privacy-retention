<?php

namespace Contoweb\PrivacyRetention\Concerns;

use Closure;
use Contoweb\PrivacyRetention\Anonymizer;
use Contoweb\PrivacyRetention\Enums\AnonymizationStrategy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Adds anonymization behavior to a model, mirroring the SoftDeletes pattern.
 *
 * Requires the model to implement \Contoweb\PrivacyRetention\Contracts\Anonymizable.
 */
trait HasRetentionPolicy
{
    /**
     * Boot the trait by merging the anonymized_at cast and registering events.
     */
    public function initializeHasRetentionPolicy(): void
    {
        $this->mergeCasts([$this->getAnonymizedAtColumn() => 'datetime']);
        $this->addObservableEvents(['anonymizing', 'anonymized']);
    }

    /**
     * Register an "anonymizing" model event callback.
     */
    public static function anonymizing(string|array|Closure $callback): void
    {
        static::registerModelEvent('anonymizing', $callback);
    }

    /**
     * Register an "anonymized" model event callback.
     */
    public static function anonymized(string|array|Closure $callback): void
    {
        static::registerModelEvent('anonymized', $callback);
    }

    /**
     * Scope a query to records that have not been anonymized yet.
     */
    public function scopeNotAnonymized(Builder $query): Builder
    {
        return $query->whereNull($this->getAnonymizedAtColumn());
    }

    /**
     * Scope a query to records that have already been anonymized.
     */
    public function scopeAnonymized(Builder $query): Builder
    {
        return $query->whereNotNull($this->getAnonymizedAtColumn());
    }

    /**
     * Anonymize the model's personal data and stamp anonymized_at.
     *
     * @throws Throwable
     */
    public function anonymize(): void
    {
        if ($this->isAnonymized()) {
            return;
        }

        if ($this->fireModelEvent('anonymizing') === false) {
            return;
        }

        $anonymizer = app(Anonymizer::class);

        DB::connection($this->getConnectionName())->transaction(function () use ($anonymizer): void {
            foreach ($this->anonymizableFields() as $field => $strategy) {
                $value = $this->{$field};

                $this->{$field} = match (true) {
                    $strategy instanceof AnonymizationStrategy => $anonymizer->apply($strategy, $value),
                    is_callable($strategy)                     => $strategy($value),
                    default                                    => throw new InvalidArgumentException(sprintf(
                        'Anonymization strategy for field [%s] on [%s] must be an %s or callable, %s given.',
                        $field,
                        static::class,
                        AnonymizationStrategy::class,
                        get_debug_type($strategy),
                    )),
                };
            }

            $this->{$this->getAnonymizedAtColumn()} = now();

            $this->shouldFireAnonymizationEvents() ? $this->save() : $this->saveQuietly();
        });

        $this->fireModelEvent('anonymized', false);
    }

    /**
     * Whether this record has already been anonymized.
     */
    public function isAnonymized(): bool
    {
        return $this->{$this->getAnonymizedAtColumn()} !== null;
    }

    /**
     * Column used to stamp the anonymization timestamp.
     * Override on the model to use a different column name.
     */
    public function getAnonymizedAtColumn(): string
    {
        return 'anonymized_at';
    }

    /**
     * Whether the surrounding Eloquent save events should fire when the model
     * is anonymized. Override on the model to opt in or out per-model.
     */
    public function shouldFireAnonymizationEvents(): bool
    {
        return (bool) config('privacy-retention.events', true);
    }
}
