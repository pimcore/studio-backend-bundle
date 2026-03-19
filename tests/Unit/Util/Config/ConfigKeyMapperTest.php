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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Util\Config;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Util\Config\ConfigKeyMapper;

/**
 * @internal
 */
final class ConfigKeyMapperTest extends Unit
{
    private ConfigKeyMapper $mapper;

    protected function _before(): void
    {
        $this->mapper = new ConfigKeyMapper();
    }

    public function testSnakeToCamelConvertsWhitelistedKeys(): void
    {
        $input = [
            'widgets_left' => ['widget1'],
            'widgets_right' => [],
            'expanded_left' => 'some_widget',
            'name' => 'test',
        ];

        $result = $this->mapper->mapKeysForApp($input);

        $this->assertArrayHasKey('widgetsLeft', $result);
        $this->assertArrayHasKey('widgetsRight', $result);
        $this->assertArrayHasKey('expandedLeft', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayNotHasKey('widgets_left', $result);
        $this->assertArrayNotHasKey('widgets_right', $result);
        $this->assertArrayNotHasKey('expanded_left', $result);
    }

    public function testSnakeToCamelPreservesNonWhitelistedKeys(): void
    {
        $input = [
            'predefined_columns' => [['key' => 'id']],
            'data_object' => ['some_value'],
            'widget_type' => 'element_tree',
            'name' => 'test',
        ];

        $result = $this->mapper->mapKeysForApp($input);

        $this->assertArrayHasKey('predefined_columns', $result);
        $this->assertArrayHasKey('data-object', $result);
        $this->assertArrayHasKey('widget_type', $result);
        $this->assertArrayHasKey('name', $result);
    }

    public function testCamelToSnakeConvertsWhitelistedKeys(): void
    {
        $input = [
            'widgetsLeft' => ['widget1'],
            'expandedRight' => null,
            'contextPermissions' => [],
            'name' => 'test',
        ];

        $result = $this->mapper->mapKeysForConfig($input);

        $this->assertArrayHasKey('widgets_left', $result);
        $this->assertArrayHasKey('expanded_right', $result);
        $this->assertArrayHasKey('context_permissions', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayNotHasKey('widgetsLeft', $result);
    }

    public function testCamelToSnakePreservesNonWhitelistedKeys(): void
    {
        $input = [
            'name' => 'test',
            'icon' => ['type' => 'name', 'value' => 'pimcore'],
            'classes' => [],
            'pql' => null,
        ];

        $result = $this->mapper->mapKeysForConfig($input);

        $this->assertSame($input, $result);
    }

    public function testRecursiveConversion(): void
    {
        $input = [
            'widgets_left' => [
                'widget1' => [
                    'element_type' => 'document',
                    'root_folder' => '/',
                    'show_root' => true,
                    'page_size' => null,
                    'context_permissions' => [],
                    'is_writeable' => false,
                    'widget_type' => 'element_tree',
                    'name' => 'Documents',
                ],
            ],
        ];

        $result = $this->mapper->mapKeysForApp($input);

        $this->assertArrayHasKey('widgetsLeft', $result);
        $widget = $result['widgetsLeft']['widget1'];
        $this->assertArrayHasKey('elementType', $widget);
        $this->assertArrayHasKey('rootFolder', $widget);
        $this->assertArrayHasKey('showRoot', $widget);
        $this->assertArrayHasKey('pageSize', $widget);
        $this->assertArrayHasKey('contextPermissions', $widget);
        $this->assertArrayHasKey('isWriteable', $widget);
        // Non-whitelisted keys preserved
        $this->assertArrayHasKey('widget_type', $widget);
        $this->assertArrayHasKey('name', $widget);
    }

    public function testStaticMethodsMatchInstanceMethods(): void
    {
        $input = [
            'widgets_left' => ['element_type' => 'asset'],
        ];

        $this->assertSame(
            ConfigKeyMapper::convertKeysForApp($input),
            $this->mapper->mapKeysForApp($input)
        );

        $camelInput = [
            'widgetsLeft' => ['elementType' => 'asset'],
        ];

        $this->assertSame(
            ConfigKeyMapper::convertKeysForConfig($camelInput),
            $this->mapper->mapKeysForConfig($camelInput)
        );
    }

    public function testRoundTripConversion(): void
    {
        $snakeCase = [
            'widgets_left' => [
                'w1' => [
                    'element_type' => 'document',
                    'root_folder' => '/',
                    'show_root' => true,
                    'page_size' => 50,
                    'context_permissions' => [],
                    'name' => 'Docs',
                    'widget_type' => 'element_tree',
                    'icon' => ['type' => 'name', 'value' => 'doc'],
                ],
            ],
            'widgets_right' => [],
            'widgets_bottom' => [],
            'expanded_left' => 'w1',
            'expanded_right' => null,
            'context_permissions' => [],
            'name' => 'Default',
        ];

        $camelCase = $this->mapper->mapKeysForApp($snakeCase);
        $backToSnake = $this->mapper->mapKeysForConfig($camelCase);

        $this->assertSame($snakeCase, $backToSnake);
    }

    public function testEmptyArrayReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->mapper->mapKeysForApp([]));
        $this->assertSame([], $this->mapper->mapKeysForConfig([]));
    }

    public function testAlreadyCamelCasePassesThroughSnakeToCamel(): void
    {
        $input = [
            'widgetsLeft' => ['widget1'],
            'elementType' => 'document',
            'name' => 'test',
        ];

        $result = $this->mapper->mapKeysForApp($input);

        // Already camelCase keys are NOT in the snake→camel whitelist,
        // so they pass through unchanged
        $this->assertArrayHasKey('widgetsLeft', $result);
        $this->assertArrayHasKey('elementType', $result);
        $this->assertArrayHasKey('name', $result);
    }

    public function testGdprKeysConversion(): void
    {
        $input = [
            'data_objects' => [
                'classes' => [
                    'Person' => ['allow_delete' => true],
                ],
            ],
            'assets' => [
                'types' => ['image', 'video'],
            ],
        ];

        $result = $this->mapper->mapKeysForApp($input);

        $this->assertArrayHasKey('data_objects', $result);
        $this->assertArrayHasKey('classes', $result['data_objects']);
        $this->assertArrayHasKey('allowDelete', $result['data_objects']['classes']['Person']);
        // Non-whitelisted keys preserved
        $this->assertArrayHasKey('assets', $result);
        $this->assertArrayHasKey('types', $result['assets']);
    }
}
