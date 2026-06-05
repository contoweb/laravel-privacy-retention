<?php

namespace Contoweb\PrivacyRetention\Enums;

use Contoweb\PrivacyRetention\Support\Anonymizer;

enum AnonymizationStrategy: string
{
    case String = 'string';
    case Email = 'email';
    case Nullify = 'nullify';
    case Fixed = 'fixed';

    /**
     * Apply this strategy by dispatching to the matching Anonymizer method.
     */
    public function apply(mixed $value): mixed
    {
        return Anonymizer::{$this->value}($value);
    }
}
