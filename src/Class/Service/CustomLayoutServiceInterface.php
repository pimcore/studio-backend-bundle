<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service;

use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CustomLayoutNewParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CustomLayoutUpdateParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\CustomLayout\CustomLayout;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\CustomLayout\CustomLayoutCompact;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\Exception\DefinitionWriteException;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @internal
 */
interface CustomLayoutServiceInterface
{
    /**
     * @return CustomLayoutCompact[]
     */
    public function getCustomLayoutCollection(string $dataObjectClass): array;

    /**
     * @throws NotFoundException
     */
    public function getCustomLayout(string $customLayoutId): CustomLayout;

    /**
     * @throws DefinitionWriteException|NotFoundException
     */
    public function deleteCustomLayout(string $customLayoutId): void;

    /**
     * @throws DefinitionWriteException
     */
    public function createCustomLayout(
        string $customLayoutId,
        CustomLayoutNewParameters $customLayoutParameters
    ): CustomLayout;

    /**
     * @throws DefinitionWriteException
     */
    public function updateCustomLayout(
        string $customLayoutId,
        CustomLayoutUpdateParameters $customLayoutParameters
    ): CustomLayout;

    public function exportCustomLayoutAsJson(string $customLayoutId): JsonResponse;
}
