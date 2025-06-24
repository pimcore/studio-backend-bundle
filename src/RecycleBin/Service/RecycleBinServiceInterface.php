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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ItemsParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;

/**
 * @internal
 */
interface RecycleBinServiceInterface
{
    public function listRecycleBin(CollectionFilterParameter $parameters): Collection;

    /**
     * @throws EnvironmentException|NotFoundException
     */
    public function restore(ItemsParameter $parameter): ?int;

    /**
     * @throws EnvironmentException|NotFoundException
     */
    public function delete(ItemsParameter $parameter): ?int;

    public function flushRecycleBin(): void;

    /**
     * @throws EnvironmentException|NotFoundException
     */
    public function restoreItem(int $id): void;

    /**
     * @throws EnvironmentException|NotFoundException
     */
    public function deleteItem(int $id): void;
}
