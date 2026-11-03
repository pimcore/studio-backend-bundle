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

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig\Listing;

/**
 * @internal
 */
interface CollectionRepositoryInterface
{
    /**
     * @throws InvalidArgumentException
     */
    public function getListing(FilterParameter $parameters, int $storeId): Listing;

    /**
     * @throws NotFoundException
     */
    public function getById(int $id): CollectionConfig;

    /**
     * One page of the store's collections, ordered by identifier, so that a configuration transfer
     * never holds a whole store at once.
     *
     * @return CollectionConfig[]
     */
    public function getChunkByStore(int $storeId, int $limit, int $offset): array;

    /**
     * One page of the store's collections, taken after the given row id, which is the paging an apply
     * walks the target in - it writes while it walks, and an offset into an identifier ordering
     * would skip the rows an adopted identifier moved past.
     *
     * @return CollectionConfig[]
     */
    public function getChunkAfterId(int $storeId, int $afterId, int $limit): array;

    /**
     * Whether a collection of the store already carries the name, which is the rule
     * {@see self::create()} refuses a write over - a rename is not checked, because
     * {@see self::update()} does not check one either. Asked before an artifact is applied, so that
     * the collision is reported as a conflict of the reconcile rather than written past the rule.
     *
     * @throws DatabaseException
     */
    public function isNameTaken(int $storeId, string $name): bool;

    /**
     * Writes the artifact's values onto the given configuration, or onto a new one when there is
     * none, stamping the identifier on a row that does not carry one yet.
     *
     * @throws ElementSavingFailedException
     */
    public function saveFromArtifact(
        ?CollectionConfig $config,
        int $storeId,
        string $identifier,
        string $name,
        ?string $description,
    ): CollectionConfig;

    /**
     * @throws InvalidArgumentException
     * @throws ElementSavingFailedException
     */
    public function create(string $name, int $storeId): CollectionConfig;

    /**
     * @throws NotFoundException
     * @throws ElementSavingFailedException
     */
    public function update(int $id, string $name, ?string $description): CollectionConfig;

    /**
     * @throws NotFoundException
     * @throws Exception
     */
    public function delete(int $id): void;
}
