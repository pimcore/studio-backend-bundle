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

namespace Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ItemsParameter;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Model\RestoreResult;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Schema\RelocatedItem;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface RecycleBinServiceInterface
{
    public function listRecycleBin(CollectionFilterParameter $parameters): Collection;

    /**
     * @throws EnvironmentException|InvalidElementTypeException|NotFoundException|UserNotFoundException
     */
    public function restore(ItemsParameter $parameter): RestoreResult;

    /**
     * @throws EnvironmentException|NotFoundException
     */
    public function delete(ItemsParameter $parameter): ?int;

    public function flushRecycleBin(): void;

    /**
     * Restores elements whose parent is missing into the fallback folder, the given user (default: the current
     * user) needs the publish permission on it.
     *
     * @return RelocatedItem[] the elements restored into the fallback folder
     *
     * @throws EnvironmentException|InvalidElementTypeException|NotFoundException|UserNotFoundException
     */
    public function restoreItem(int $id, ?UserInterface $user = null): array;

    /**
     * @throws EnvironmentException|NotFoundException
     */
    public function deleteItem(int $id): void;
}
