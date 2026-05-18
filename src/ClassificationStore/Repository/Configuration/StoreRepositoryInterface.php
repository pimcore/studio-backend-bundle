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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;

/**
 * @internal
 */
interface StoreRepositoryInterface
{
    /**
     * @return StoreConfig[]
     */
    public function listStores(): array;

    /**
     * @throws NotFoundException
     */
    public function getById(int $id): StoreConfig;

    /**
     * @throws InvalidArgumentException
     * @throws ElementSavingFailedException
     */
    public function create(string $name): StoreConfig;

    /**
     * @throws NotFoundException
     * @throws InvalidArgumentException
     * @throws ElementSavingFailedException
     */
    public function update(int $id, string $name, ?string $description): StoreConfig;
}
