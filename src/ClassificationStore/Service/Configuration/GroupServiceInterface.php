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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Configuration;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\GroupCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\GroupDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\GroupUpdate;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;

/**
 * @internal
 */
interface GroupServiceInterface
{
    /**
     * @throws InvalidArgumentException
     */
    public function listGroups(
        CollectionFilterParameter $parameters,
        int $storeId,
    ): Collection;

    /**
     * @throws InvalidArgumentException
     * @throws ElementSavingFailedException
     */
    public function createGroup(GroupCreate $parameters): GroupDetail;

    /**
     * @throws NotFoundException
     * @throws ElementSavingFailedException
     */
    public function updateGroup(int $id, GroupUpdate $parameters): GroupDetail;

    /**
     * @throws NotFoundException
     */
    public function deleteGroup(int $id): void;
}
