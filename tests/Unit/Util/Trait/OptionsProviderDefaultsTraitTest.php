<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Util\Trait;

use Codeception\Test\Unit;
use Pimcore\Bundle\CoreBundle\OptionsProvider\SelectOptionsOptionsProvider;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\OptionsProviderDefaultsTrait;

/**
 * @internal
 */
final class OptionsProviderDefaultsTraitTest extends Unit
{
    public function testSelectOptionsWithoutClassGetsTheDefaultProvider(): void
    {
        $result = $this->createTraitHelper()->apply([
            'fieldtype' => 'select',
            'optionsProviderType' => 'select_options',
            'optionsProviderData' => 'AardingWCD',
        ]);

        $this->assertSame(SelectOptionsOptionsProvider::class, $result['optionsProviderClass']);
        $this->assertSame('AardingWCD', $result['optionsProviderData']);
    }

    public function testEmptyStringClassIsTreatedAsMissing(): void
    {
        $result = $this->createTraitHelper()->apply([
            'optionsProviderType' => 'select_options',
            'optionsProviderClass' => '',
        ]);

        $this->assertSame(SelectOptionsOptionsProvider::class, $result['optionsProviderClass']);
    }

    public function testConfiguredClassIsKept(): void
    {
        $config = [
            'optionsProviderType' => 'select_options',
            'optionsProviderClass' => 'App\\OptionsProvider\\MyProvider',
        ];

        $this->assertSame($config, $this->createTraitHelper()->apply($config));
    }

    public function testOtherProviderTypesAreUntouched(): void
    {
        $configure = ['optionsProviderType' => 'configure', 'options' => [['key' => 'a', 'value' => 'a']]];
        $class = ['optionsProviderType' => 'class', 'optionsProviderClass' => ''];
        $none = ['fieldtype' => 'input', 'name' => 'plain'];

        $helper = $this->createTraitHelper();

        $this->assertSame($configure, $helper->apply($configure));
        $this->assertSame($class, $helper->apply($class));
        $this->assertSame($none, $helper->apply($none));
    }

    public function testChildrenAreProcessedRecursively(): void
    {
        $result = $this->createTraitHelper()->apply([
            'fieldtype' => 'panel',
            'children' => [
                [
                    'fieldtype' => 'fieldset',
                    'children' => [
                        ['fieldtype' => 'select', 'optionsProviderType' => 'select_options'],
                        ['fieldtype' => 'input'],
                    ],
                ],
                ['fieldtype' => 'multiselect', 'optionsProviderType' => 'select_options'],
                'not-an-array',
            ],
        ]);

        $this->assertSame(
            SelectOptionsOptionsProvider::class,
            $result['children'][0]['children'][0]['optionsProviderClass']
        );
        $this->assertArrayNotHasKey('optionsProviderClass', $result['children'][0]['children'][1]);
        $this->assertSame(SelectOptionsOptionsProvider::class, $result['children'][1]['optionsProviderClass']);
        $this->assertSame('not-an-array', $result['children'][2]);
    }

    private function createTraitHelper(): object
    {
        return new class() {
            use OptionsProviderDefaultsTrait;

            public function apply(array $config): array
            {
                return $this->applyOptionsProviderDefaults($config);
            }
        };
    }
}
