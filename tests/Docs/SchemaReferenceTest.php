<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Docs;

use CodeIgniter\Test\CIUnitTestCase;
use Ephraitech\Forms\Tests\Support\SchemaReference;

/**
 * Fails when docs/reference/schema-keys.md no longer matches
 * FormSchema::describe(). Fix it with: composer docs:reference
 *
 * @internal
 */
final class SchemaReferenceTest extends CIUnitTestCase
{
    private const PAGE = __DIR__ . '/../../docs/reference/schema-keys.md';

    public function testSchemaReferenceIsUpToDate(): void
    {
        $generated = SchemaReference::markdown();

        if (getenv('DOCS_UPDATE') === '1') {
            file_put_contents(self::PAGE, $generated);
        }

        $this->assertFileExists(self::PAGE, 'Generate it with: composer docs:reference');
        $this->assertSame(
            $generated,
            (string) file_get_contents(self::PAGE),
            'docs/reference/schema-keys.md is out of date. Regenerate it with: composer docs:reference',
        );
    }
}
