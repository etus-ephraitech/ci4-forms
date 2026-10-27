<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Feature;

use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;
use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Form;

/**
 * Rendering rows and fieldsets with the Bootstrap 5 theme.
 *
 * @internal
 */
final class LayoutRenderingTest extends CIUnitTestCase
{
    private Forms $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new Forms();
        Services::resetSingle('validation');
    }

    private function make(): Form
    {
        return Form::make('order', $this->config)->setOldInput(null)->setOldErrors(null);
    }

    /**
     * Removes the view debug comments and collapses whitespace between tags.
     */
    private function html(string $html): string
    {
        $html = (string) preg_replace('/<!-- DEBUG-VIEW (START|ENDED) [^>]*-->\n?/', '', $html);

        return trim((string) preg_replace('/>\s+</', '><', $html));
    }

    public function testRowWithWidths(): void
    {
        $form = $this->make();
        $form->row(static function (Form $form): void {
            $form->text('first_name')->width(6);
            $form->text('last_name')->width(6);
        });

        $html = $this->html($form->fields());

        $this->assertStringStartsWith('<div class="row"><div class="col-md-6"><div class="mb-3"><label for="order_first_name"', $html);
        $this->assertSame(2, substr_count($html, '<div class="col-md-6">'));
    }

    public function testFieldsWithoutWidthShareTheRow(): void
    {
        $form = $this->make();
        $form->row(static function (Form $form): void {
            $form->text('city')->width(8);
            $form->text('postcode');
        });

        $html = $this->html($form->fields());

        $this->assertStringContainsString('<div class="col-md-8">', $html);
        $this->assertStringContainsString('<div class="col-md"><div class="mb-3"><label for="order_postcode"', $html);
    }

    public function testHiddenFieldsTakeNoColumn(): void
    {
        $form = $this->make();
        $form->row(static function (Form $form): void {
            $form->text('city');
            $form->hidden('zone', 'A');
        });

        $html = $this->html($form->fields());

        $this->assertSame(1, substr_count($html, 'class="col-md"'));
        $this->assertStringContainsString('</div></div><input type="hidden" name="zone" value="A"></div>', $html);
    }

    public function testFieldsetWithLegendDescriptionAndId(): void
    {
        $form = $this->make();
        $form->fieldset('Delivery address', static function (Form $form): void {
            $form->text('street');
        })->description('Where should we send it?')->id('delivery')->addClass('border p-3');

        $html = $this->html($form->fields());

        $this->assertStringStartsWith(
            '<fieldset id="delivery" class="mb-4 border p-3"><legend class="h6 mb-3">Delivery address</legend><p class="text-body-secondary small mb-3">Where should we send it?</p><div class="mb-3"><label for="order_street"',
            $html,
        );
        $this->assertStringEndsWith('</fieldset>', $html);
    }

    public function testLegendIsEscapedAndOptional(): void
    {
        $form = $this->make();
        $form->fieldset('<b>Bold</b>', static function (Form $form): void {
            $form->text('a');
        });
        $form->fieldset('', static function (Form $form): void {
            $form->text('b');
        });

        $html = $this->html($form->fields());

        $this->assertStringContainsString('&lt;b&gt;Bold&lt;/b&gt;', $html);
        $this->assertSame(1, substr_count($html, '<legend'));
    }

    public function testNestedRowInsideFieldset(): void
    {
        $form = $this->make();
        $form->fieldset('Address', static function (Form $form): void {
            $form->row(static function (Form $form): void {
                $form->text('city')->width(8);
                $form->text('postcode')->width(4);
            });
        });

        $html = $this->html($form->fields());

        $this->assertMatchesRegularExpression('#^<fieldset class="mb-4"><legend[^>]*>Address</legend><div class="row"><div class="col-md-8">.*<div class="col-md-4">.*</div></div></fieldset>$#', $html);
    }

    public function testFieldsetsSideBySideInARow(): void
    {
        $form = $this->make();
        $form->row(static function (Form $form): void {
            $form->fieldset('Billing', static function (Form $form): void {
                $form->text('billing_city');
            })->width(6);
            $form->fieldset('Shipping', static function (Form $form): void {
                $form->text('shipping_city');
            })->width(6);
        });

        $html = $this->html($form->fields());

        $this->assertStringStartsWith('<div class="row"><div class="col-md-6"><fieldset class="mb-4"><legend class="h6 mb-3">Billing</legend>', $html);
        $this->assertStringContainsString('<div class="col-md-6"><fieldset class="mb-4"><legend class="h6 mb-3">Shipping</legend>', $html);
    }

    public function testRowClassesAndThemeOverrides(): void
    {
        $this->config->themes['bootstrap5']['classes']['column'] = 'col-lg-{width}';
        $this->config->themes['bootstrap5']['classes']['row']    = 'row g-2';

        $form = $this->make();
        $form->row(static function (Form $form): void {
            $form->text('a')->width(3);
        })->addClass('align-items-end');

        $this->assertStringStartsWith('<div class="row g-2 align-items-end"><div class="col-lg-3">', $this->html($form->fields()));
    }

    public function testEmptyGroupsAreNotRendered(): void
    {
        $form = $this->make();
        $form->fieldset('Account', static function (Form $form): void {
            $form->row(static function (Form $form): void {
                $form->password('password');
            });
        });
        $form->text('name');
        $form->remove('password');

        $html = $this->html($form->fields());

        $this->assertStringNotContainsString('<fieldset', $html);
        $this->assertStringNotContainsString('class="row"', $html);
        $this->assertStringContainsString('name="name"', $html);
    }

    public function testWholeFormUsesTheLayout(): void
    {
        $form = $this->make();
        $form->row(static function (Form $form): void {
            $form->text('first_name')->width(6);
            $form->text('last_name')->width(6);
        });
        $form->submit('Save');

        $html = $this->html($form->render());

        $this->assertStringContainsString('<div class="row"><div class="col-md-6">', $html);
        $this->assertMatchesRegularExpression('#</div></div></div><div class="mb-3"><button type="submit"#', $html);
    }

    public function testNamedFieldsIgnoreTheLayout(): void
    {
        $form = $this->make();
        $form->row(static function (Form $form): void {
            $form->text('first_name')->width(6);
            $form->text('last_name')->width(6);
        });

        $html = $this->html($form->fields('last_name', 'first_name'));

        $this->assertStringNotContainsString('class="row"', $html);
        $this->assertLessThan(strpos($html, 'first_name'), strpos($html, 'last_name'));
    }

    public function testErrorsRenderInsideColumns(): void
    {
        $form = $this->make();
        $form->row(static function (Form $form): void {
            $form->email('email')->rules('required|valid_email')->width(6);
        });
        $form->validate(['email' => 'bad']);

        $this->assertStringContainsString('<div class="col-md-6"><div class="mb-3"><label for="order_email"', $this->html($form->fields()));
        $this->assertStringContainsString('invalid-feedback', $form->fields());
    }
}
