<?php

namespace Contoweb\PrivacyRetention\Tests\Fixtures;

use Contoweb\PrivacyRetention\Concerns\HasRetentionPolicy;
use Contoweb\PrivacyRetention\Contracts\Anonymizable;
use Contoweb\PrivacyRetention\Enums\AnonymizationStrategy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MaskedUser extends Model implements Anonymizable
{
    use HasRetentionPolicy;

    protected $table = 'masked_users';

    protected $guarded = [];

    public function anonymizableFields(): array
    {
        return [
            'name' => AnonymizationStrategy::String,
        ];
    }

    public static function anonymizableQuery(): Builder
    {
        return self::query();
    }

    public function getAnonymizedAtColumn(): string
    {
        return 'masked_at';
    }
}
