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
interface ConfigKeyMapperInterface
{
    /**
     * Convert whitelisted snake_case keys in the array to camelCase.
     * Applies recursively to nested arrays.
     */
    public function mapKeysForApp(array $data): array;

    /**
     * Convert whitelisted camelCase keys in the array to snake_case.
     * Applies recursively to nested arrays.
     */
    public function mapKeysForConfig(array $data): array;
}
