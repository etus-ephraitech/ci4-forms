<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\StreamFilterTrait;

/**
 * The spark command is discovered and reports errors cleanly. Writing files
 * is covered by ViewPublisherTest, so nothing is written to the framework's
 * test app here.
 *
 * @internal
 */
final class PublishCommandTest extends CIUnitTestCase
{
    use StreamFilterTrait;

    public function testCommandIsListed(): void
    {
        command('list');

        $this->assertStringContainsString('forms:publish', $this->getStreamFilterBuffer());
    }

    public function testUnknownThemeReportsError(): void
    {
        command('forms:publish --theme tailwind');

        $this->assertStringContainsString(
            'Form theme "tailwind" is not registered. Available themes: bootstrap5.',
            $this->getStreamFilterBuffer(),
        );
    }
}
