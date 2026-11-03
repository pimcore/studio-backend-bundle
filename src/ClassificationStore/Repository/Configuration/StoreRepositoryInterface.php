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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
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

    public function getByIdentifier(string $identifier): ?StoreConfig;

    /**
     * The store itself and every collection, group, sub group and key in it that carries no
     * identifier, named by level and name - what a configuration export has to refuse over.
     *
     * @return list<array{level: string, name: string}>
     *
     * @throws DatabaseException
     */
    public function findEntitiesWithoutIdentifier(int $storeId): array;

    /**
     * Whether another store already carries the name, which is the rule {@see self::create()} and
     * {@see self::update()} refuse a write over. Asked before an artifact is applied, so that the
     * collision is reported as a conflict of the reconcile rather than as a failed save.
     */
    public function isNameTaken(string $name, ?int $exceptId): bool;

    /**
     * Writes the artifact's store entry onto the given configuration, or onto a new one when there
     * is none, stamping the identifier on a row that does not carry one yet.
     *
     * @throws ElementSavingFailedException
     */
    public function saveFromArtifact(
        ?StoreConfig $config,
        string $identifier,
        string $name,
        ?string $description,
    ): StoreConfig;

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
