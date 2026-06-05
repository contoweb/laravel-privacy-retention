<?php

namespace Contoweb\PrivacyRetention\Tests\Fixtures;

use Contoweb\PrivacyRetention\Concerns\HasRetentionPolicy;
use Contoweb\PrivacyRetention\Contracts\Anonymizable;
use Contoweb\PrivacyRetention\Enums\AnonymizationStrategy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TestUser extends Model implements Anonymizable
{
    use HasRetentionPolicy;

    protected $table = 'test_users';

    protected $guarded = [];

    const ANONYMIZE_MONTHS = 12;

    public function anonymizableFields(): array
    {
        return [
            'name' => AnonymizationStrategy::String,
            'email' => AnonymizationStrategy::Email,
            'notes' => AnonymizationStrategy::Nullify,
            'status' => AnonymizationStrategy::Fixed,
            'phone' => fn (mixed $value): string => 'hash-'.md5((string) $value),
        ];
    }

    public static function anonymizableQuery(): Builder
    {
        return self::query()
            ->where('active', false)
            ->where('deactivated_at', '<=', now()->subMonths(self::ANONYMIZE_MONTHS));
    }
}
