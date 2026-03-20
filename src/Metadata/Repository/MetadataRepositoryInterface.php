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

namespace Pimcore\Bundle\StudioBackendBundle\Metadata\Repository;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException as ApiInvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Metadata\MappedParameter\MetadataParameters;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Schema\UpdatePredefinedMetadata;
use Pimcore\Model\Metadata\Predefined;

/**
 * @internal
 */
interface MetadataRepositoryInterface
{
    /**
     * @return Predefined[]
     */
    public function getAllPredefinedMetadata(): array;

    public function getAllPredefinedMetadataDefinitions(MetadataParameters $metadataParameters): array;

    public function getPredefinedMetadataByName(string $name): ?Predefined;

    /**
     * @throws NotFoundException
     */
    public function getPredefinedMetadataById(string $id): Predefined;

    /**
     * @throws NotWriteableException
     */
    public function createPredefinedMetadata(): Predefined;

    /**
     * @throws NotFoundException
     * @throws NotWriteableException
     * @throws ApiInvalidArgumentException
     */
    public function updatePredefinedMetadata(
        string $id,
        UpdatePredefinedMetadata $metadata,
    ): Predefined;

    /**
     * @throws NotFoundException
     * @throws NotWriteableException
     */
    public function deletePredefinedMetadata(string $id): void;

    /**
     * @return Predefined[]
     */
    public function getPredefinedMetadataByTargetType(
        ?string $subType,
        ?string $group,
    ): array;
}
