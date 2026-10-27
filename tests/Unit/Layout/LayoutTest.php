<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Unit\Layout;

use CodeIgniter\Test\CIUnitTestCase;
use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Exceptions\FormException;
use Ephraitech\Forms\Fields\Text;
use Ephraitech\Forms\Form;
use Ephraitech\Forms\Layout\Fieldset;
use Ephraitech\Forms\Layout\Row;
use RuntimeException;

/**
 * Building the layout tree.
 *
 * @internal
 */
final class LayoutTest extends CIUnitTestCase
{
    private function make(): Form
    {
        return Form::make('order', new Forms());
    }

    public function testFieldsOutsideGroupsAreTopLevel(): void
    {
        $form = $this->make();
        $form->text('a');
        $form->text('b');

        $this->assertSame(['a', 'b'], $form->getLayout());
    }

    public function testRowCollectsItsFields(): void
    {
        $form = $this->make();
        $form->text('before');
        $row = $form->row(static function (Form $form): void {
            $form->text('first_name')->width(6);
            $form->text('last_name')->width(6);
        });
        $form->text('after');

        $layout = $form->getLayout();

        $this->assertInstanceOf(Row::class, $row);
        $this->assertSame('before', $layout[0]);
        $this->assertSame($row, $layout[1]);
        $this->assertSame('after', $layout[2]);
        $this->assertSame(['first_name', 'last_name'], $row->getChildren());
    }

    public function testFieldsStayFlatForValuesAndValidation(): void
    {
        $form = $this->make();
        $form->fieldset('Contact', static function (Form $form): void {
            $form->row(static function (Form $form): void {
                $form->text('first_name');
            });
            $form->email('email');
        });

        $this->assertSame(['first_name', 'email'], array_keys($form->getFields()));
    }

    public function testNestedGroups(): void
    {
        $form = $this->make();
        $fieldset = $form->fieldset('Delivery address', static function (Form $form): void {
            $form->text('street');
            $form->row(static function (Form $form): void {
                $form->text('city')->width(8);
                $form->text('postcode')->width(4);
            });
        })->description('Where to deliver.')->id('delivery');

        $this->assertInstanceOf(Fieldset::class, $fieldset);
        $this->assertSame('Delivery address', $fieldset->getLegend());
        $this->assertSame('Where to deliver.', $fieldset->getDescription());
        $this->assertSame('delivery', $fieldset->getId());

        [$street, $row] = $fieldset->getChildren();

        $this->assertSame('street', $street);
        $this->assertInstanceOf(Row::class, $row);
        $this->assertSame(['city', 'postcode'], $row->getChildren());
    }

    public function testDefineClosureCanUseThisInFormClasses(): void
    {
        $form = new class (null, new Forms()) extends Form {
            protected function define(): void
            {
                $this->row(function (): void {
                    $this->text('first_name')->width(6);
                    $this->text('last_name')->width(6);
                });
            }
        };

        $this->assertInstanceOf(Row::class, $form->getLayout()[0]);
        $this->assertSame(['first_name', 'last_name'], $form->getLayout()[0]->getChildren());
    }

    public function testInlineChainingAfterAGroup(): void
    {
        $form = Form::make('signup', new Forms())
            ->row(static function (Form $form): void {
                $form->text('first_name');
            })
            ->email('email')
            ->end();

        $this->assertInstanceOf(Form::class, $form);
        $this->assertSame(['first_name', 'email'], array_keys($form->getFields()));
        $this->assertSame('email', $form->getLayout()[1]);
    }

    public function testGroupClosesEvenWhenDefinitionThrows(): void
    {
        $form = $this->make();

        try {
            $form->row(static function (): void {
                throw new RuntimeException('boom');
            });
        } catch (RuntimeException) {
        }

        $form->text('after');

        $this->assertSame('after', $form->getLayout()[1]);
    }

    public function testRemoveTakesFieldsOutOfGroups(): void
    {
        $form = $this->make();
        $form->fieldset('Account', static function (Form $form): void {
            $form->email('email');
            $form->row(static function (Form $form): void {
                $form->password('password');
                $form->password('password_confirm');
            });
        });

        $form->remove('password', 'password_confirm');

        /** @var Fieldset $fieldset */
        $fieldset = $form->getLayout()[0];
        $row      = $fieldset->getChildren()[1];

        $this->assertSame([], $row->getChildren());
        $this->assertTrue($row->isEmpty());
        $this->assertFalse($fieldset->isEmpty());

        $form->remove('email');

        $this->assertTrue($fieldset->isEmpty());
    }

    public function testFieldsAddedLaterGoToTheTopLevel(): void
    {
        $form = $this->make();
        $form->row(static function (Form $form): void {
            $form->text('a');
        });
        $form->add(new Text('late'));

        $this->assertSame('late', $form->getLayout()[1]);
    }

    public function testWidths(): void
    {
        $this->assertSame(6, (new Text('a'))->width(6)->getWidth());
        $this->assertNull((new Text('a'))->width(6)->width(null)->getWidth());
        $this->assertSame(4, (new Fieldset('x'))->width(4)->getWidth());
    }

    public function testInvalidWidthsThrow(): void
    {
        foreach ([static fn () => (new Text('a'))->width(0), static fn () => (new Text('a'))->width(13), static fn () => (new Row())->width(-1)] as $call) {
            try {
                $call();
                $this->fail('expected an exception');
            } catch (FormException $e) {
                $this->assertStringContainsString('between 1 and 12', $e->getMessage());
            }
        }
    }

    public function testContainerWithoutFormThrows(): void
    {
        $this->expectException(FormException::class);
        $this->expectExceptionMessage('This row does not belong to a form.');

        (new Row())->end();
    }

    public function testClassesAndToArray(): void
    {
        $form = $this->make();
        $form->row(static function (Form $form): void {
            $form->text('a')->width(6);
        })->addClass('g-3 align-items-end');

        $data = $form->toArray();

        $this->assertSame(
            ['type' => 'row', 'classes' => ['g-3', 'align-items-end'], 'width' => null, 'children' => ['a']],
            $data['layout'][0],
        );
        $this->assertSame(6, $data['fields'][0]['width']);
    }
}
