<?php

declare(strict_types=1);

namespace Ephraitech\Forms\Tests\Feature\Schema;

use CodeIgniter\Test\CIUnitTestCase;
use Ephraitech\Forms\Config\Forms;
use Ephraitech\Forms\Schema\FormSchema;
use Ephraitech\Forms\Schema\SchemaPolicy;
use Ephraitech\Forms\Tests\Support\Fields\MoneyField;
use Ephraitech\Forms\Tests\Support\Providers\ServiceOptions;

/**
 * FormSchema::describe(), and that it never disagrees with the loader.
 *
 * @internal
 */
final class DescribeTest extends CIUnitTestCase
{
    private Forms $config;

    protected function setUp(): void
    {
        parent::setUp();

        ServiceOptions::$calls         = 0;
        $this->config                  = new Forms();
        $this->config->fieldTypes      = ['money' => MoneyField::class];
        $this->config->optionProviders = ['services' => [ServiceOptions::class, 'all'], 'staff' => [ServiceOptions::class, 'all']];
    }

    public function testRestrictedDescription(): void
    {
        $d = FormSchema::describe(null, $this->config);

        $this->assertSame(1, $d['version']);
        $this->assertSame('restricted', $d['policy']);
        $this->assertNotContains('action', $d['form']['keys']);
        $this->assertSame(['get', 'post', 'put', 'patch', 'delete'], $d['form']['methods']);

        $this->assertArrayHasKey('text', $d['types']);
        $this->assertArrayHasKey('row', $d['types']);
        $this->assertArrayNotHasKey('password', $d['types']);
        $this->assertArrayNotHasKey('hidden', $d['types']);
        $this->assertArrayNotHasKey('money', $d['types'], 'custom types need to be allowed');

        $this->assertSame('int', $d['rules']['max_length']);
        $this->assertArrayNotHasKey('is_unique', $d['rules']);
        $this->assertContains('aria-*', $d['attributes']);
        $this->assertNotContains('image/svg+xml', $d['mimes']);
        $this->assertSame(10240, $d['max_upload_kb']);
        $this->assertContains('is_admin', $d['forbidden_names']);
        $this->assertSame(100, $d['limits']['max_fields']);
        $this->assertSame(['services', 'staff'], $d['providers']);
        $this->assertSame(0, ServiceOptions::$calls, 'describing never calls providers');
    }

    public function testTypeDetails(): void
    {
        $types = FormSchema::describe(null, $this->config)['types'];

        $this->assertSame('Dropdown', $types['select']['label']);
        $this->assertFalse($types['select']['group']);
        $this->assertSame('optional', $types['select']['multiple']);
        $this->assertTrue($types['checkboxes']['multiple']);
        $this->assertSame([['type'], ['name'], ['options', 'options_from']], $types['select']['requires']);
        $this->assertSame([['options', 'options_from']], $types['select']['exclusive']);
        $this->assertSame([], $types['text']['exclusive']);
        $this->assertSame([['type'], ['name'], ['max_size'], ['mimes']], $types['file']['requires']);
        $this->assertSame([['type']], $types['submit']['requires']);
        $this->assertSame(['kind' => 'options_with_groups', 'help' => 'Choices as value => label.'], $types['select']['keys']['options']);
        $this->assertSame('options', $types['radio']['keys']['options']['kind']);
        $this->assertSame('whole_step', $types['time']['keys']['step']['kind']);
        $this->assertSame('step', $types['number']['keys']['step']['kind']);
        $this->assertSame('Button text.', $types['submit']['keys']['label']['help']);
        $this->assertTrue($types['fieldset']['group']);
        $this->assertArrayHasKey('legend', $types['fieldset']['keys']);
        $this->assertSame([['children'], ['legend']], $types['fieldset']['requires']);
    }

    public function testPolicyChangesTheDescription(): void
    {
        $policy = SchemaPolicy::restricted()->allowTypes('money')->disallowProviders('staff')->allowAction();
        $d      = FormSchema::describe($policy, $this->config);

        $this->assertTrue($d['types']['money']['custom']);
        $this->assertSame(['kind' => 'mixed', 'help' => 'Custom setting of this field type.'], $d['types']['money']['keys']['currency']);
        $this->assertSame(['services'], $d['providers']);
        $this->assertContains('action', $d['form']['keys']);
    }

    public function testTrustedDescription(): void
    {
        $d = FormSchema::describe(SchemaPolicy::trusted(), $this->config);

        $this->assertSame('trusted', $d['policy']);
        $this->assertArrayHasKey('password', $d['types']);
        $this->assertArrayHasKey('money', $d['types']);
        $this->assertNull($d['rules'], 'every rule allowed');
        $this->assertNull($d['attributes']);
        $this->assertNull($d['mimes']);
        $this->assertNull($d['limits']);
        $this->assertSame([], $d['forbidden_names']);
        $this->assertSame([['type'], ['name']], $d['types']['file']['requires']);
    }

    public function testNoProvidersMeansNoOptionsFromKey(): void
    {
        $this->config->optionProviders = [];

        $types = FormSchema::describe(null, $this->config)['types'];

        $this->assertArrayNotHasKey('options_from', $types['select']['keys']);
        $this->assertSame([['type'], ['name'], ['options']], $types['select']['requires']);
    }

    public function testDescriptionIsJsonReady(): void
    {
        $d = FormSchema::describe(null, $this->config);

        $this->assertSame($d, json_decode((string) json_encode($d, JSON_THROW_ON_ERROR), true));
    }

    // -- Consistency with the loader ------------------------------------------

    /**
     * @return array<string, array{SchemaPolicy}>
     */
    public static function policies(): array
    {
        return [
            'restricted'            => [SchemaPolicy::restricted()],
            'trusted'               => [SchemaPolicy::trusted()],
            'restricted with money' => [SchemaPolicy::restricted()->allowTypes('money')],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('policies')]
    public function testEveryDescribedKeyIsAcceptedByTheLoader(SchemaPolicy $policy): void
    {
        $description = FormSchema::describe($policy, $this->config);
        $checked     = 0;

        foreach ($description['types'] as $type => $info) {
            $base = $this->minimalNode($type, $info);

            foreach (array_keys($info['keys']) as $key) {
                if (array_key_exists($key, $base)) {
                    continue;
                }

                $node = $base;

                // A builder must drop a key's exclusive partner, as here.
                foreach ($info['exclusive'] as $group) {
                    if (in_array($key, $group, true)) {
                        foreach ($group as $partner) {
                            unset($node[$partner]);
                        }
                    }
                }

                $node[$key] = $this->sample($key, $info['keys'][$key]['kind'], $type, $description);

                if ($key === 'each_rules' && $type === 'select') {
                    $node['multiple'] = true;
                }

                $this->assertValid($node, $policy, $type . '.' . $key);
                $checked++;
            }
        }

        $this->assertGreaterThan(200, $checked, 'every type and key was exercised');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('policies')]
    public function testEveryTypeWithAllItsKeysAtOnce(SchemaPolicy $policy): void
    {
        $description = FormSchema::describe($policy, $this->config);

        foreach ($description['types'] as $type => $info) {
            $node = $this->minimalNode($type, $info);

            foreach ($info['keys'] as $key => $spec) {
                if (! array_key_exists($key, $node) && ! ($key === 'options_from' && isset($node['options']))) {
                    $node[$key] = $this->sample($key, $spec['kind'], $type, $description);
                }
            }

            if ($type === 'select') {
                $node['multiple'] = true;
                $node['default']  = ['a'];
            }

            $this->assertValid($node, $policy, $type . ' with every key');
        }
    }

    /**
     * The smallest valid node of a type, from the description's requirements.
     *
     * @param array<string, mixed> $info
     *
     * @return array<string, mixed>
     */
    private function minimalNode(string $type, array $info): array
    {
        $node = ['type' => $type];

        foreach ($info['requires'] as $alternatives) {
            $key = $alternatives[0];

            $node[$key] = match ($key) {
                'type'     => $type,
                'name'     => 'sample',
                'options'  => ['a' => 'A', 'b' => 'B'],
                'max_size' => 100,
                'mimes'    => ['image/png'],
                'children' => [],
                'legend'   => 'Section',
                default    => 'x',
            };
        }

        return $node;
    }

    /**
     * @param array<string, mixed> $description
     */
    private function sample(string $key, string $kind, string $type, array $description): mixed
    {
        $multipleType = $type === 'checkboxes';

        return match (true) {
            $key === 'extensions'   => ['png'],
            $key === 'mimes'        => ['image/png'],
            $key === 'currency'     => 'USD',
            $key === 'options_from' => $description['providers'][0],
            $key === 'default'      => $multipleType ? ['a'] : ($type === 'checkbox' ? '1' : 'a'),
            default                 => match ($kind) {
                'text', 'optional_text' => 'Example',
                'bool'                  => true,
                'positive_int'          => 3,
                'width'                 => 6,
                'rules'                 => 'required',
                'messages'              => ['required' => 'Needed.'],
                'options', 'options_with_groups' => ['a' => 'A'],
                'attributes'            => ['title' => 'Hint'],
                'classes'               => 'extra',
                'step'                  => 2,
                'whole_step'            => 60,
                'checked_value'         => 'yes',
                'dimensions'            => [100, 100],
                'children'              => [],
                default                 => 'x',
            },
        };
    }

    /**
     * @param array<string, mixed> $node
     */
    private function assertValid(array $node, SchemaPolicy $policy, string $what): void
    {
        $check = FormSchema::check(['fields' => [$node]], $policy, $this->config);

        $this->assertTrue($check->isValid(), $what . ' was described but rejected: ' . implode(' | ', $check->getMessages()));
    }
}
