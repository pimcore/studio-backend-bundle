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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Service\Widget;

use Pimcore\Bundle\StudioBackendBundle\Element\Model\ContextPermissionData;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;

/**
 * @internal
 */
interface TreeContextPermissionsServiceInterface
{
    /**
     * @throws InvalidElementTypeException
     *
     * @return array<string, bool>
     */
    public function list(string $elementType, array $treeContextPermissions): array;

    /**
     * @throws InvalidElementTypeException|InvalidArgumentException
     *
     * @return array<string, bool>
     */
    public function update(ContextPermissionData $permissionData): array;

    /**
     * @throws InvalidElementTypeException
     *
     * @return array<string, bool>
     */
    public function updatePermissions(string $elementType, array $permissionData): array;
}
