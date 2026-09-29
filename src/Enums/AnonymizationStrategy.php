<?php

namespace Contoweb\PrivacyRetention\Enums;

enum AnonymizationStrategy: string
{
    case String = 'string';
    case Email = 'email';
    case Nullify = 'nullify';
    case Fixed = 'fixed';
}
