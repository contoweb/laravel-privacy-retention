<?php

namespace Contoweb\PrivacyRetention\Tests;

use Contoweb\PrivacyRetention\Concerns\HasRetentionPolicy;
use Contoweb\PrivacyRetention\Contracts\Anonymizable;
use Contoweb\PrivacyRetention\Tests\Fixtures\MaskedUser;
use Contoweb\PrivacyRetention\Tests\Fixtures\TestUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class HasRetentionPolicyTest extends TestCase
{
    protected function tearDown(): void
    {
        TestUser::flushEventListeners();
        MaskedUser::flushEventListeners();

        parent::tearDown();
    }

    public function test_anonymize_applies_every_strategy_and_stamps_timestamp(): void
    {
        $user = TestUser::create([
            'name'           => 'John Doe',
            'email'          => 'john@example.com',
            'notes'          => 'secret',
            'status'         => 'active',
            'phone'          => '0791234567',
            'active'         => false,
            'deactivated_at' => Carbon::now()->subYears(2),
        ]);

        $user->anonymize();

        $this->assertSame('J*****n D*****e', $user->name);
        $this->assertStringContainsString('@anonymized.invalid', $user->email);
        $this->assertNull($user->notes);
        $this->assertSame('anonymized', $user->status);
        $this->assertSame('hash-' . md5('0791234567'), $user->phone);
        $this->assertNotNull($user->anonymized_at);
        $this->assertTrue($user->isAnonymized());
    }

    public function test_not_anonymized_scope_excludes_stamped_records(): void
    {
        TestUser::create(['name' => 'A', 'anonymized_at' => Carbon::now()]);
        TestUser::create(['name' => 'B']);

        $this->assertSame(1, TestUser::query()->notAnonymized()->count());
    }

    public function test_anonymizing_and_anonymized_events_fire(): void
    {
        $calls = [];

        TestUser::anonymizing(function (TestUser $user) use (&$calls): void {
            $calls[] = ['anonymizing', $user->name];
        });

        TestUser::anonymized(function (TestUser $user) use (&$calls): void {
            $calls[] = ['anonymized', $user->name];
        });

        $user = TestUser::create(['name' => 'John Doe']);
        $user->anonymize();

        $this->assertSame([
            ['anonymizing', 'John Doe'],
            ['anonymized', 'J*****n D*****e'],
        ], $calls);
    }

    public function test_anonymizing_listener_returning_false_cancels(): void
    {
        TestUser::anonymizing(fn () => false);

        $user = TestUser::create(['name' => 'John Doe']);
        $user->anonymize();

        $this->assertSame('John Doe', $user->fresh()->name);
        $this->assertNull($user->fresh()->anonymized_at);
    }

    public function test_anonymize_is_idempotent(): void
    {
        $user = TestUser::create(['name' => 'John Doe']);
        $user->anonymize();

        $maskedName = $user->name;
        $stampedAt = $user->anonymized_at;

        $user->anonymize();

        $this->assertSame($maskedName, $user->name);
        $this->assertTrue($stampedAt->equalTo($user->anonymized_at));
    }

    public function test_anonymize_uses_custom_column(): void
    {
        $user = MaskedUser::create(['name' => 'John Doe']);
        $user->anonymize();

        $this->assertSame('J*****n D*****e', $user->name);
        $this->assertNotNull($user->masked_at);
        $this->assertTrue($user->isAnonymized());
        $this->assertSame(0, MaskedUser::query()->notAnonymized()->count());
    }

    public function test_callable_strategy_receives_value(): void
    {
        $received = null;

        $user = new class extends Model implements Anonymizable
        {
            use HasRetentionPolicy;

            protected $table = 'test_users';

            protected $guarded = [];

            public static ?\Closure $capture = null;

            public function anonymizableFields(): array
            {
                return [
                    'name' => function (mixed $value): string {
                        (self::$capture)($value);

                        return 'masked';
                    },
                ];
            }

            public static function anonymizableQuery(): Builder
            {
                return self::query();
            }
        };

        $user::$capture = function ($value) use (&$received): void {
            $received = $value;
        };

        $user->fill(['name' => 'original'])->save();
        $user->anonymize();

        $this->assertSame('original', $received);
        $this->assertSame('masked', $user->name);
    }

    public function test_invalid_strategy_type_throws(): void
    {
        $user = new class extends Model implements Anonymizable
        {
            use HasRetentionPolicy;

            protected $table = 'test_users';

            protected $guarded = [];

            public function anonymizableFields(): array
            {
                return ['name' => 'not-a-strategy'];
            }

            public static function anonymizableQuery(): Builder
            {
                return self::query();
            }
        };

        $user->fill(['name' => 'John'])->save();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be an');

        $user->anonymize();
    }
}
