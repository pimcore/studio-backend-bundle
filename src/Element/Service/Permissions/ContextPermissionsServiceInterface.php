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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service\Permissions;

use Pimcore\Bundle\StudioBackendBundle\Element\Model\ContextPermissionData;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;

interface ContextPermissionsServiceInterface
{
    public function add(ContextPermissionData $contextPermissionData): void;

    public function getDefaultValue(string $key, string $elementType): bool;

    /**
     * @throws InvalidElementTypeException
     *
     * @return array<string, bool>
     */
    public function list(string $elementType): array;

    /**
     * @throws InvalidElementTypeException
     */
    public function remove(string $key, string $elementType): void;
}
