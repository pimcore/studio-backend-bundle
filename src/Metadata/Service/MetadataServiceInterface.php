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

namespace Pimcore\Bundle\StudioBackendBundle\Metadata\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException as ApiInvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Metadata\MappedParameter\MetadataParameters;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Schema\CustomMetadata;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Schema\PredefinedMetadata;
use Pimcore\Bundle\StudioBackendBundle\Metadata\Schema\UpdatePredefinedMetadata;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;

/**
 * @internal
 */
interface MetadataServiceInterface
{
    public const DEFAULT_METADATA = ['title', 'alt', 'copyright'];

    /**
     * @return array<int, CustomMetadata>
     *
     * @throws ForbiddenException|NotFoundException
     *
     */
    public function getCustomMetadata(int $id): array;

    public function getPredefinedMetadata(MetadataParameters $parameters): Collection;

    /**
     * @throws NotWriteableException
     */
    public function createPredefinedMetadata(): PredefinedMetadata;

    /**
     * @throws NotFoundException
     */
    public function getPredefinedMetadataById(string $id): PredefinedMetadata;

    /**
     * @throws NotFoundException
     * @throws NotWriteableException
     * @throws ApiInvalidArgumentException
     */
    public function updatePredefinedMetadata(string $id, UpdatePredefinedMetadata $metadata): PredefinedMetadata;

    /**
     * @throws NotFoundException
     * @throws NotWriteableException
     */
    public function deletePredefinedMetadata(string $id): void;

    /**
     * @return array<int, PredefinedMetadata>
     */
    public function getAssetPredefinedMetadata(
        ?string $subType,
        ?string $group,
    ): array;
}
