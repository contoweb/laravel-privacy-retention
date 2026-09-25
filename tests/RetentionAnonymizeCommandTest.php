<?php

namespace Contoweb\PrivacyRetention\Tests;

use Contoweb\PrivacyRetention\Contracts\Anonymizable;
use Contoweb\PrivacyRetention\Tests\Fixtures\TestUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RetentionAnonymizeCommandTest extends TestCase
{
    public function test_command_anonymizes_records_past_retention(): void
    {
        config()->set('privacy-retention.models', [TestUser::class]);

        $stale = TestUser::create([
            'name'           => 'Old User',
            'email'          => 'old@example.com',
            'active'         => false,
            'deactivated_at' => now()->subYears(2),
        ]);

        $fresh = TestUser::create([
            'name'   => 'Active User',
            'email'  => 'active@example.com',
            'active' => true,
        ]);

        Artisan::call('retention:anonymize');

        $this->assertTrue($stale->fresh()->isAnonymized());
        $this->assertFalse($fresh->fresh()->isAnonymized());
    }

    public function test_command_warns_when_no_models_configured(): void
    {
        config()->set('privacy-retention.models', []);

        $exit = Artisan::call('retention:anonymize');

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('No models configured', Artisan::output());
    }

    public function test_command_rejects_models_missing_contract(): void
    {
        config()->set('privacy-retention.models', [\stdClass::class]);

        $this->expectException(RuntimeException::class);

        Artisan::call('retention:anonymize');
    }

    public function test_command_rejects_models_missing_trait(): void
    {
        config()->set('privacy-retention.models', [FakeAnonymizable::class]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must use the');

        Artisan::call('retention:anonymize');
    }

    public function test_command_chunks_through_large_result_sets(): void
    {
        config()->set('privacy-retention.models', [TestUser::class]);

        foreach (range(1, 2500) as $i) {
            TestUser::create([
                'name'           => 'User ' . $i,
                'active'         => false,
                'deactivated_at' => now()->subYears(2),
            ]);
        }

        Artisan::call('retention:anonymize');

        $this->assertSame(2500, TestUser::query()->whereNotNull('anonymized_at')->count());
        $this->assertStringContainsString('Anonymized 2500', Artisan::output());
    }
}

class FakeAnonymizable implements Anonymizable
{
    public function anonymizableFields(): array
    {
        return [];
    }

    public static function anonymizableQuery(): Builder
    {
        return new Builder(DB::query());
    }
}
