<?php

namespace Contoweb\PrivacyRetention\Tests;

use Contoweb\PrivacyRetention\Anonymizer;
use Contoweb\PrivacyRetention\Enums\AnonymizationStrategy;

class AnonymizationStrategyTest extends TestCase
{
    public function test_string_masks_words(): void
    {
        $result = $this->app->make(Anonymizer::class)->string('John Doe');

        $this->assertSame('J*****n D*****e', $result);
    }

    public function test_string_returns_null_for_null(): void
    {
        $this->assertNull($this->app->make(Anonymizer::class)->string(null));
    }

    public function test_email_returns_anonymized_address(): void
    {
        $this->assertMatchesRegularExpression(
            '/^anonymized-[0-9a-f-]+@anonymized\.invalid$/',
            $this->app->make(Anonymizer::class)->email()
        );
    }

    public function test_nullify_returns_null(): void
    {
        $this->assertNull($this->app->make(Anonymizer::class)->nullify());
    }

    public function test_fixed_returns_literal(): void
    {
        $this->assertSame('anonymized', $this->app->make(Anonymizer::class)->fixed());
    }

    public function test_apply_string_masks_words(): void
    {
        $this->assertSame(
            'J*****n D*****e',
            $this->app->make(Anonymizer::class)->apply(AnonymizationStrategy::String, 'John Doe')
        );
    }

    public function test_apply_email_returns_anonymized_address(): void
    {
        $this->assertMatchesRegularExpression(
            '/^anonymized-[0-9a-f-]+@anonymized\.invalid$/',
            $this->app->make(Anonymizer::class)->apply(AnonymizationStrategy::Email, 'john@example.com')
        );
    }

    public function test_apply_nullify_returns_null(): void
    {
        $this->assertNull($this->app->make(Anonymizer::class)->apply(AnonymizationStrategy::Nullify, 'anything'));
    }

    public function test_apply_fixed_returns_literal(): void
    {
        $this->assertSame(
            'anonymized',
            $this->app->make(Anonymizer::class)->apply(AnonymizationStrategy::Fixed, 'anything')
        );
    }

    public function test_config_mask_length_is_used(): void
    {
        $this->app['config']->set('privacy-retention.anonymizer.mask_length', 3);

        $this->assertSame('J***n D***e', $this->app->make(Anonymizer::class)->string('John Doe'));
    }

    public function test_config_mask_character_is_used(): void
    {
        $this->app['config']->set('privacy-retention.anonymizer.mask_character', '#');

        $this->assertSame('J#####n D#####e', $this->app->make(Anonymizer::class)->string('John Doe'));
    }

    public function test_config_email_domain_is_used(): void
    {
        $this->app['config']->set('privacy-retention.anonymizer.email_domain', 'example.test');

        $this->assertMatchesRegularExpression(
            '/^anonymized-[0-9a-f-]+@example\.test$/',
            $this->app->make(Anonymizer::class)->email()
        );
    }

    public function test_config_fixed_value_is_used(): void
    {
        $this->app['config']->set('privacy-retention.anonymizer.fixed_value', 'redacted');

        $this->assertSame(
            'redacted',
            $this->app->make(Anonymizer::class)->apply(AnonymizationStrategy::Fixed, 'anything')
        );
    }
}
