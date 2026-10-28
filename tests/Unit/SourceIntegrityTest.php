<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Guards against a docblock that lost its closing "*\/" and silently turned
 * the following methods, constants or properties into comment text. Such a
 * file still parses, so neither the linter nor PHP itself complains; the
 * members are simply gone.
 *
 * @internal
 */
final class SourceIntegrityTest extends CIUnitTestCase
{
    public function testNoClassMembersAreHiddenInComments(): void
    {
        $source = dirname(__DIR__, 2) . '/src';
        $files  = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS));
        $found  = [];

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php' || str_contains($file->getPathname(), '/Views/')) {
                continue;
            }

            foreach (token_get_all((string) file_get_contents($file->getPathname())) as $token) {
                if (! is_array($token) || ! in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }

                if (preg_match('/^\s*(?:public|protected|private)\s+(?:static\s+|readonly\s+)?(?:const|function)\s+\w+/m', $token[1]) === 1) {
                    $found[] = substr($file->getPathname(), strlen($source) + 1) . ':' . $token[2];
                }
            }
        }

        $this->assertSame([], $found, 'A comment contains class members; a closing */ is probably missing.');
    }
}
