<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Unit\Support;

use CodeIgniter\Test\CIUnitTestCase;
use Ephraitech\Forms\Support\RuleParser;

/**
 * @internal
 */
final class RuleParserTest extends CIUnitTestCase
{
    public function testSplitsRuleString(): void
    {
        $this->assertSame(
            ['required', 'min_length[3]', 'max_length[100]'],
            RuleParser::split('required|min_length[3]|max_length[100]'),
        );
    }

    public function testKeepsPipesInsideBrackets(): void
    {
        $this->assertSame(
            ['required', 'regex_match[/^(cash|card)$/]'],
            RuleParser::split('required|regex_match[/^(cash|card)$/]'),
        );
    }

    public function testIgnoresEmptySegmentsAndWhitespace(): void
    {
        $this->assertSame(['required', 'valid_email'], RuleParser::split(' required || valid_email | '));
        $this->assertSame([], RuleParser::split(''));
    }

    public function testCleansRuleArrayAndKeepsCallables(): void
    {
        $callable = static fn ($value): bool => $value !== 'x';

        $rules = RuleParser::split([' required ', $callable]);

        $this->assertSame('required', $rules[0]);
        $this->assertSame($callable, $rules[1]);
    }

    public function testRejectsInvalidArrayEntries(): void
    {
        $this->assertNull(RuleParser::split(['required', '']));
        $this->assertNull(RuleParser::split(['required', 42]));
    }

    public function testNameAndParam(): void
    {
        $this->assertSame('max_length', RuleParser::name('max_length[100]'));
        $this->assertSame('100', RuleParser::param('max_length[100]'));
        $this->assertSame('required', RuleParser::name('required'));
        $this->assertNull(RuleParser::param('required'));
        $this->assertSame('/^(a|b)$/', RuleParser::param('regex_match[/^(a|b)$/]'));
    }

    public function testHasAndParamOf(): void
    {
        $rules = ['required', 'max_length[50]', 'in_list[a,b]'];

        $this->assertTrue(RuleParser::has($rules, 'required'));
        $this->assertFalse(RuleParser::has($rules, 'permit_empty'));
        $this->assertSame('a,b', RuleParser::paramOf($rules, 'in_list'));
        $this->assertSame('', RuleParser::paramOf($rules, 'required'));
        $this->assertNull(RuleParser::paramOf($rules, 'min_length'));
    }

    public function testHasDoesNotMatchSimilarNames(): void
    {
        $this->assertFalse(RuleParser::has(['required_with[email]'], 'required'));
        $this->assertFalse(RuleParser::has(['permit_empty'], 'required'));
    }

    public function testNumericParamOf(): void
    {
        $rules = ['max_length[100]', 'greater_than_equal_to[0.5]', 'in_list[a,b]'];

        $this->assertSame(100, RuleParser::numericParamOf($rules, 'max_length'));
        $this->assertSame(0.5, RuleParser::numericParamOf($rules, 'greater_than_equal_to'));
        $this->assertNull(RuleParser::numericParamOf($rules, 'in_list'));
        $this->assertNull(RuleParser::numericParamOf($rules, 'min_length'));
    }
}
