<?php

namespace Contoweb\PrivacyRetention\Tests;

use Contoweb\PrivacyRetention\Enums\AnonymizationStrategy;
use Contoweb\PrivacyRetention\Support\Anonymizer;

class AnonymizationStrategyTest extends TestCase
{
    public function test_string_masks_words(): void
    {
        $result = Anonymizer::string('John Doe');

        $this->assertSame('J*****n D*****e', $result);
    }

    public function test_string_returns_null_for_null(): void
    {
        $this->assertNull(Anonymizer::string(null));
    }

    public function test_email_returns_anonymized_address(): void
    {
        $this->assertMatchesRegularExpression(
            '/^anonymized-[0-9a-f-]+@anonymized\.invalid$/',
            Anonymizer::email('john@example.com')
        );
    }

    public function test_nullify_returns_null(): void
    {
        $this->assertNull(Anonymizer::nullify('anything'));
    }

    public function test_fixed_returns_literal(): void
    {
        $this->assertSame('anonymized', Anonymizer::fixed('anything'));
    }

    public function test_enum_string_dispatches_to_anonymizer(): void
    {
        $this->assertSame('J*****n D*****e', AnonymizationStrategy::String->apply('John Doe'));
    }

    public function test_enum_email_dispatches_to_anonymizer(): void
    {
        $this->assertMatchesRegularExpression(
            '/^anonymized-[0-9a-f-]+@anonymized\.invalid$/',
            AnonymizationStrategy::Email->apply('john@example.com')
        );
    }

    public function test_enum_nullify_dispatches_to_anonymizer(): void
    {
        $this->assertNull(AnonymizationStrategy::Nullify->apply('anything'));
    }

    public function test_enum_fixed_dispatches_to_anonymizer(): void
    {
        $this->assertSame('anonymized', AnonymizationStrategy::Fixed->apply('anything'));
    }
}
