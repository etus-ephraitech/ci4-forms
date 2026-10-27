<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Unit;

use CodeIgniter\CodeIgniter;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Confirms the test environment matches the package's supported platform.
 *
 * If this fails in CI, the problem is the environment (PHP or CodeIgniter
 * version), not the package code.
 *
 * @internal
 */
final class EnvironmentTest extends CIUnitTestCase
{
    public function testPhpVersionIsSupported(): void
    {
        $this->assertTrue(
            version_compare(PHP_VERSION, '8.2.0', '>='),
            'ephraitech/ci4-forms requires PHP 8.2 or newer; running ' . PHP_VERSION,
        );
    }

    public function testCodeIgniterVersionIsSupported(): void
    {
        $this->assertTrue(
            version_compare(CodeIgniter::CI_VERSION, '4.6.0', '>='),
            'ephraitech/ci4-forms requires CodeIgniter 4.6 or newer; running ' . CodeIgniter::CI_VERSION,
        );
    }

    public function testPackageNamespaceIsRegistered(): void
    {
        $prefixes = require HOMEPATH . 'vendor/composer/autoload_psr4.php';

        $this->assertArrayHasKey('Ephraitech\\Forms\\', $prefixes);
        $this->assertSame(
            realpath(HOMEPATH . 'src'),
            realpath($prefixes['Ephraitech\\Forms\\'][0]),
        );
    }
}
