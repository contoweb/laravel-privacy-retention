<?php

namespace Contoweb\PrivacyRetention;

use Contoweb\PrivacyRetention\Enums\AnonymizationStrategy;
use Illuminate\Support\Str;

class Anonymizer
{
    public function __construct(
        protected readonly string $maskCharacter = '*',
        protected readonly int $maskLength = 5,
        protected readonly string $emailDomain = 'anonymized.invalid',
        protected readonly string $fixedValue = 'anonymized',
    ) {}

    public function apply(AnonymizationStrategy $strategy, mixed $value): mixed
    {
        return match ($strategy) {
            AnonymizationStrategy::String  => $this->string($value),
            AnonymizationStrategy::Email   => $this->email(),
            AnonymizationStrategy::Nullify => $this->nullify(),
            AnonymizationStrategy::Fixed   => $this->fixed(),
        };
    }

    public function string(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return Str::of((string) $value)
            ->explode(' ')
            ->map(fn ($word) => $this->maskWord($word))
            ->implode(' ');
    }

    public function email(): string
    {
        return 'anonymized-' . Str::uuid() . '@' . $this->emailDomain;
    }

    public function nullify(): null
    {
        return null;
    }

    public function fixed(): string
    {
        return $this->fixedValue;
    }

    protected function maskWord(string $word): string
    {
        if ($word === '') {
            return '';
        }

        $mask = str_repeat($this->maskCharacter, $this->maskLength);

        if (Str::length($word) === 1) {
            return $word . $mask;
        }

        return Str::substr($word, 0, 1) . $mask . Str::substr($word, -1);
    }
}
