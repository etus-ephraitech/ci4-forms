<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Unit\Config;

use CodeIgniter\Test\CIUnitTestCase;
use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Exceptions\FormException;

/**
 * @internal
 */
final class FormsTest extends CIUnitTestCase
{
    private Forms $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new Forms();
    }

    public function testDefaultThemeIsBootstrap5(): void
    {
        $theme = $this->config->activeTheme();

        $this->assertSame('Ephraitech\Forms\Views\bootstrap5', $theme['views']);
        $this->assertStringContainsString('*', $theme['required_marker']);
        $this->assertSame('form-control', $theme['classes']['input']);
    }

    public function testClassForReturnsThemeClass(): void
    {
        $this->assertSame('form-select', $this->config->classFor('select'));
        $this->assertSame('is-invalid', $this->config->classFor('invalid'));
    }

    public function testClassForFallsBackToDefaultForUnknownElement(): void
    {
        $this->assertSame('', $this->config->classFor('does_not_exist'));
        $this->assertSame('fallback', $this->config->classFor('does_not_exist', 'fallback'));
    }

    public function testViewPathJoinsThemeAndTemplate(): void
    {
        $this->assertSame(
            'Ephraitech\Forms\Views\bootstrap5/field_input',
            $this->config->viewPath('field_input'),
        );
        $this->assertSame(
            'Ephraitech\Forms\Views\bootstrap5/field_input',
            $this->config->viewPath('/field_input'),
        );
    }

    public function testCustomThemeCanBeRegisteredAndSelected(): void
    {
        $this->config->themes['plain'] = [
            'views'   => 'forms/plain/',
            'classes' => ['input' => 'input'],
        ];
        $this->config->theme = 'plain';

        $theme = $this->config->activeTheme();

        $this->assertSame('forms/plain', $theme['views']);
        $this->assertSame('', $theme['required_marker']);
        $this->assertSame('input', $this->config->classFor('input'));
        $this->assertSame('', $this->config->classFor('select'));
        $this->assertSame('forms/plain/form', $this->config->viewPath('form'));
    }

    public function testSingleClassCanBeOverridden(): void
    {
        $this->config->themes['bootstrap5']['classes']['submit'] = 'btn btn-success';

        $this->assertSame('btn btn-success', $this->config->classFor('submit'));
        $this->assertSame('form-control', $this->config->classFor('input'));
    }

    public function testUnknownThemeThrows(): void
    {
        $this->config->theme = 'missing';

        $this->expectException(FormException::class);
        $this->expectExceptionMessage('Form theme "missing" is not registered. Available themes: bootstrap5.');

        $this->config->activeTheme();
    }

    public function testThemeWithoutViewsThrows(): void
    {
        $this->config->themes['broken'] = ['views' => '   '];

        $this->expectException(FormException::class);
        $this->expectExceptionMessage('a non-empty "views" path is required.');

        $this->config->themeDefinition('broken');
    }

    public function testThemeWithNonStringClassThrows(): void
    {
        $this->config->themes['broken'] = [
            'views'   => 'forms/broken',
            'classes' => ['input' => ['form-control']],
        ];

        $this->expectException(FormException::class);
        $this->expectExceptionMessage('"classes" must map element names to class strings.');

        $this->config->themeDefinition('broken');
    }

    public function testNormaliseMethodLowercasesSupportedMethods(): void
    {
        $this->assertSame('post', $this->config->normaliseMethod(' POST '));
        $this->assertSame('patch', $this->config->normaliseMethod('Patch'));
        $this->assertSame('get', $this->config->normaliseMethod('get'));
    }

    public function testNormaliseMethodRejectsUnsupportedMethod(): void
    {
        $this->expectException(FormException::class);
        $this->expectExceptionMessage('HTTP method "options" is not supported for forms.');

        $this->config->normaliseMethod('OPTIONS');
    }

    public function testDefaultMethodIsValidated(): void
    {
        $this->assertSame('post', $this->config->defaultMethod());

        $this->config->defaultMethod = 'head';

        $this->expectException(FormException::class);

        $this->config->defaultMethod();
    }

    public function testBehaviourDefaults(): void
    {
        $this->assertTrue($this->config->csrf);
        $this->assertTrue($this->config->spoofMethods);
        $this->assertTrue($this->config->novalidate);
        $this->assertTrue($this->config->ruleAttributes);
        $this->assertFalse($this->config->showErrorSummary);
        $this->assertTrue($this->config->useOldInput);
        $this->assertTrue($this->config->prefixIdsWithFormName);
        $this->assertTrue($this->config->trimStrings);
        $this->assertTrue($this->config->emptyStringsToNull);
        $this->assertSame(0, $this->config->checkboxUncheckedValue);
    }
}
