<?php

namespace Contoweb\PrivacyRetention;

use Illuminate\Support\Str;

class Anonymizer
{
    public static function string(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return Str::of((string) $value)
            ->explode(' ')
            ->map(fn ($word) => self::maskWord($word))
            ->implode(' ');
    }

    public static function email(mixed $value): string
    {
        return 'anonymized-' . Str::uuid() . '@anonymized.invalid';
    }

    public static function nullify(mixed $value): null
    {
        return null;
    }

    public static function fixed(mixed $value): string
    {
        return 'anonymized';
    }

    protected static function maskWord(string $word): string
    {
        if ($word === '') {
            return '';
        }

        if (Str::length($word) === 1) {
            return $word . str_repeat('*', 5);
        }

        return Str::substr($word, 0, 1) . str_repeat('*', 5) . Str::substr($word, -1);
    }
}
