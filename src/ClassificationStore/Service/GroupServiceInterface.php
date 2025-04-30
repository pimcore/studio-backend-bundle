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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\LayoutParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\ListClassificationStoreParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\GroupLayout;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;

/**
 * @internal
 */
interface GroupServiceInterface
{
    /**
     * @throws Exception
     * @throws NotFoundException
     */
    public function getGroups(ListClassificationStoreParameter $parameter): Collection;

    /**
     * @return array<int, int>
     *
     * @throws Exception
     * @throws NotFoundException
     */
    public function getAllowedGroupIds(ListClassificationStoreParameter $parameter): array;

    /**
     * @throws Exception
     * @throws NotFoundException
     */
    public function getLayoutDefinition(int $groupId, LayoutParameter $layoutParameter): GroupLayout;
}
