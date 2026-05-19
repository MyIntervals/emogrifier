<?php

declare(strict_types=1);

namespace Pelago\Emogrifier\Tests\Unit\PhpStan;

use PHPStan\Testing\RuleTestCase;
use PHPStan\Rules\Rule;

/**
 * Only one `Rule` can seemingly be covered by classes extending `RuleTestCase`.
 * This provides some common functionality and settings for `TestCase`s covering `IgnoreBooleanAlways`,
 * which involves more than one `Rule`.
 *
 * @extends RuleTestCase<Rule>
 */
abstract class IgnoreBooleanAlwaysTestBase extends RuleTestCase
{
    /**
     * @var non-empty-string
     */
    protected const FIXTURES_DIR = __DIR__ . '/../../fixtures/phpstan/';

    /**
     * @test
     */
    public function warningIsIgnoredInAssertInstanceOf(): void
    {
        // Second argument is array of expected warnings.
        $this->analyse([self::FIXTURES_DIR . 'alwaystrue-instanceof-inassert.php'], []);
    }

    /**
     * @return non-empty-array<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        return \array_merge(
            parent::getAdditionalConfigFiles(),
            [self::FIXTURES_DIR . 'ignorebooleanalways.neon']
        );
    }
}
