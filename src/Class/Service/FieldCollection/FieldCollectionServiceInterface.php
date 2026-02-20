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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service\FieldCollection;

use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CreateFieldCollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\UpdateParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldCollection\FieldCollectionDetail;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldCollectionUsageData;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
interface FieldCollectionServiceInterface
{
    public function listFieldCollections(): Collection;

    /**
     * @throws NotFoundException
     */
    public function getFieldCollectionByKey(string $key): FieldCollectionDetail;

    /**
     * @throws ElementExistsException|ElementSavingFailedException|NotWriteableException
     */
    public function createFieldCollection(CreateFieldCollectionParameters $parameters): FieldCollectionDetail;

    /**
     * @throws ElementSavingFailedException|NotFoundException|NotWriteableException
     */
    public function updateFieldCollection(string $key, UpdateParameters $parameters): FieldCollectionDetail;

    /**
     * @throws NotFoundException|NotWriteableException
     */
    public function deleteFieldCollection(string $key): void;

    /**
     * @throws NotFoundException
     */
    public function exportFieldCollection(string $key): Response;

    /**
     * @throws ElementSavingFailedException|InvalidArgumentException|NotFoundException|NotWriteableException
     */
    public function importFieldCollectionFromJson(string $key, string $json): FieldCollectionDetail;

    /**
     * @throws NotFoundException
     *
     * @return FieldCollectionUsageData[]
     */
    public function getFieldCollectionUsages(string $key): array;
}
