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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Config;

/**
 * @internal
 */
final class ConfigKeyMapper implements ConfigKeyMapperInterface
{
    /**
     * Whitelist of key mappings
     */
    private const array CONFIG_TO_APP = [
        // Perspective node keys
        'widgets_left' => 'widgetsLeft',
        'widgets_right' => 'widgetsRight',
        'widgets_bottom' => 'widgetsBottom',
        'expanded_left' => 'expandedLeft',
        'expanded_right' => 'expandedRight',
        'context_permissions' => 'contextPermissions',
        // Default Perspective
        'is_writeable' => 'isWriteable',
        // Element tree widget node keys
        'element_type' => 'elementType',
        'page_size' => 'pageSize',
        'root_folder' => 'rootFolder',
        'show_root' => 'showRoot',
        // GDPR data extractor node keys
        'allow_delete' => 'allowDelete',
        // Note types node keys
        'data_object' => 'data-object',
    ];

    private const array APP_TO_CONFIG = [
        'widgetsLeft' => 'widgets_left',
        'widgetsRight' => 'widgets_right',
        'widgetsBottom' => 'widgets_bottom',
        'expandedLeft' => 'expanded_left',
        'expandedRight' => 'expanded_right',
        'contextPermissions' => 'context_permissions',
        'elementType' => 'element_type',
        'pageSize' => 'page_size',
        'rootFolder' => 'root_folder',
        'showRoot' => 'show_root',
        'data-object' => 'data_object',
        'allowDelete' => 'allow_delete',
    ];

    public function mapKeysForApp(array $data): array
    {
        return self::convertKeysForApp($data);
    }

    public function mapKeysForConfig(array $data): array
    {
        return self::convertKeysForConfig($data);
    }

    /**
     * Static method for compile-time usage (TreeBuilder, Extension).
     * Converts whitelisted snake_case keys to camelCase, recursively.
     */
    public static function convertKeysForApp(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $newKey = self::CONFIG_TO_APP[$key] ?? $key;
            $result[$newKey] = is_array($value) ? self::convertKeysForApp($value) : $value;
        }

        return $result;
    }

    /**
     * Static method for compile-time usage (TreeBuilder, Extension).
     * Converts whitelisted camelCase keys to snake_case, recursively.
     */
    public static function convertKeysForConfig(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $newKey = self::APP_TO_CONFIG[$key] ?? $key;
            $result[$newKey] = is_array($value) ? self::convertKeysForConfig($value) : $value;
        }

        return $result;
    }
}
