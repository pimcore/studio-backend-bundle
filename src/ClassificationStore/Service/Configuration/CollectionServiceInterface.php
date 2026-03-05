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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\CollectionCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\CollectionDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\CollectionUpdate;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;

/**
 * @internal
 */
interface CollectionServiceInterface
{
    /**
     * @throws InvalidArgumentException
     */
    public function listCollections(
        CollectionFilterParameter $parameters,
        int $storeId,
    ): Collection;

    /**
     * @throws InvalidArgumentException
     * @throws ElementSavingFailedException
     */
    public function createCollection(CollectionCreate $parameters): CollectionDetail;

    /**
     * @throws NotFoundException
     * @throws ElementSavingFailedException
     */
    public function updateCollection(int $id, CollectionUpdate $parameters): CollectionDetail;

    /**
     * @throws NotFoundException
     */
    public function deleteCollection(int $id): void;
}
