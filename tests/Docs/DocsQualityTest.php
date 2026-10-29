<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Docs;

use CodeIgniter\Test\CIUnitTestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Checks across the whole documentation: the README's example is the tested
 * one, guides follow their template, the navigation lists every page, and no
 * unfinished text is published.
 *
 * @internal
 */
final class DocsQualityTest extends CIUnitTestCase
{
    private const ROOT = __DIR__ . '/../..';

    /**
     * @return list<string> Paths relative to docs/, without examples
     */
    private function pages(): array
    {
        $pages = [];
        $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::ROOT . '/docs', RecursiveDirectoryIterator::SKIP_DOTS));

        foreach ($items as $item) {
            $relative = substr($item->getPathname(), strlen(self::ROOT . '/docs/'));

            if ($item->getExtension() === 'md' && ! str_starts_with($relative, 'examples/')) {
                $pages[] = str_replace('\\', '/', $relative);
            }
        }

        sort($pages);

        return $pages;
    }

    public function testReadmeShowsTheTestedExample(): void
    {
        $readme = (string) file_get_contents(self::ROOT . '/README.md');

        foreach (['Forms/ContactForm.php', 'Controllers/Contact.php', 'Views/contact.php'] as $file) {
            $code = rtrim((string) file_get_contents(self::ROOT . '/docs/examples/app/' . $file));

            $this->assertStringContainsString($code, $readme, 'README.md must show docs/examples/app/' . $file . ' exactly');
        }
    }

    public function testReadmeLinksToTheSite(): void
    {
        $readme = (string) file_get_contents(self::ROOT . '/README.md');
        $config = (string) file_get_contents(self::ROOT . '/mkdocs.yml');

        preg_match('/^site_url:\s*(\S+)/m', $config, $match);

        $this->assertStringContainsString(rtrim($match[1], '/'), $readme);
    }

    public function testEveryPageIsInTheNavigationAndEveryEntryExists(): void
    {
        $config = (string) file_get_contents(self::ROOT . '/mkdocs.yml');
        $nav    = substr($config, (int) strpos($config, "\nnav:"));

        preg_match_all('/([\w\-\/]+\.md)\s*$/m', $nav, $matches);
        $listed = $matches[1];
        sort($listed);

        $this->assertSame($this->pages(), $listed, 'Every page under docs/ must be in the navigation, and every entry must exist');
    }

    public function testGuidesFollowTheTemplate(): void
    {
        foreach ($this->pages() as $page) {
            if (! str_starts_with($page, 'guides/') || $page === 'guides/index.md') {
                continue;
            }

            $text = (string) file_get_contents(self::ROOT . '/docs/' . $page);

            $this->assertStringContainsString("\n## Common mistakes\n", $text, $page . ' needs a "Common mistakes" section');
            $this->assertStringContainsString("\n## Related\n", $text, $page . ' needs a "Related" section');
        }
    }

    public function testNoUnfinishedText(): void
    {
        $files = [...array_map(static fn (string $page): string => 'docs/' . $page, $this->pages()), 'README.md', 'CONTRIBUTING.md'];

        foreach ($files as $file) {
            $text = (string) file_get_contents(self::ROOT . '/' . $file);

            $this->assertDoesNotMatchRegularExpression('/\b(TODO|TBD|FIXME|XXX|lorem ipsum|coming soon)\b/i', $text, $file . ' contains unfinished text');
        }
    }
}
