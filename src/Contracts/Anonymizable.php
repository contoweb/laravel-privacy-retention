<?php

namespace Contoweb\PrivacyRetention\Contracts;

use Contoweb\PrivacyRetention\Enums\AnonymizationStrategy;
use Illuminate\Database\Eloquent\Builder;

interface Anonymizable
{
    /**
     * Map of model fields to the strategy or callback used to anonymize them.
     *
     * Callables receive the current field value (which may be null) and must
     * return the anonymized value.
     *
     * @return array<string, AnonymizationStrategy|callable(mixed|null): mixed>
     */
    public function anonymizableFields(): array;

    /**
     * Query returning the records that are eligible for anonymization
     * according to the model's retention rule.
     */
    public static function anonymizableQuery(): Builder;
}
