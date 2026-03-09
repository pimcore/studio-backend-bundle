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
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig\Listing;

/**
 * @internal
 */
interface KeyRepositoryInterface
{
    /**
     * @throws InvalidArgumentException
     */
    public function getListing(FilterParameter $parameters, int $storeId): Listing;

    /**
     * @throws NotFoundException
     */
    public function getById(int $id): KeyConfig;

    /**
     * @throws ElementSavingFailedException
     */
    public function create(string $name, int $storeId): KeyConfig;

    /**
     * @throws NotFoundException
     * @throws ElementSavingFailedException
     */
    public function update(
        int $id,
        string $name,
        ?string $title,
        ?string $description,
        ?string $type,
        ?array $definition,
    ): KeyConfig;

    /**
     * @throws NotFoundException
     * @throws ElementSavingFailedException
     */
    public function softDelete(int $id): void;

    /**
     * @throws NotFoundException
     */
    public function getPageForId(
        string $table,
        int $id,
        int $storeId,
        int $pageSize,
        string $sortKey,
        string $sortDir,
    ): int;
}
