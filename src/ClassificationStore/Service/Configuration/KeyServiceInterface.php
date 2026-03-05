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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\GetPageParameters;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\GetPageResponse;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyUpdate;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;

/**
 * @internal
 */
interface KeyServiceInterface
{
    /**
     * @throws InvalidArgumentException
     */
    public function listKeys(
        CollectionFilterParameter $parameters,
        int $storeId,
    ): Collection;

    /**
     * @throws InvalidArgumentException
     * @throws ElementSavingFailedException
     */
    public function createKey(KeyCreate $parameters): KeyDetail;

    /**
     * @throws NotFoundException
     * @throws ElementSavingFailedException
     */
    public function updateKey(int $id, KeyUpdate $parameters): KeyDetail;

    /**
     * @throws NotFoundException
     * @throws ElementSavingFailedException
     */
    public function softDeleteKey(int $id): void;

    /**
     * @throws DatabaseException
     * @throws NotFoundException
     */
    public function getPage(GetPageParameters $parameters): GetPageResponse;
}
