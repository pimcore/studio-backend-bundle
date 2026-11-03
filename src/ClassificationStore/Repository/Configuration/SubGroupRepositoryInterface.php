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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConflictException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig\Listing;

/**
 * @internal
 */
interface SubGroupRepositoryInterface
{
    /**
     * @throws InvalidArgumentException
     */
    public function getListing(FilterParameter $parameters, int $storeId): Listing;

    /**
     * @throws NotFoundException
     */
    public function getById(int $id): SubGroupConfig;

    /**
     * The vocabulary entries of a store a group's keys name, keyed by identifier. An identifier with
     * no entry is left out, so a dangling tag resolves to nothing instead of to a fabricated entry.
     *
     * @param string[] $identifiers
     *
     * @return array<string, SubGroupConfig>
     */
    public function getByIdentifiers(int $storeId, array $identifiers): array;

    /**
     * One page of the store's sub groups, ordered by identifier, so that a configuration transfer
     * never holds a whole store at once.
     *
     * @return SubGroupConfig[]
     */
    public function getChunkByStore(int $storeId, int $limit, int $offset): array;

    /**
     * One page of the store's sub groups, taken after the given row id, which is the paging an apply
     * walks the target in - it writes while it walks, and an offset into an identifier ordering
     * would skip the rows an adopted identifier moved past.
     *
     * @return SubGroupConfig[]
     */
    public function getChunkAfterId(int $storeId, int $afterId, int $limit): array;

    /**
     * Writes the artifact's values onto the given configuration, or onto a new one when there is
     * none, stamping the identifier on a row that does not carry one yet.
     *
     * @throws ElementSavingFailedException
     */
    public function saveFromArtifact(
        ?SubGroupConfig $config,
        int $storeId,
        string $identifier,
        string $name,
        ?string $description,
    ): SubGroupConfig;

    /**
     * @throws InvalidArgumentException
     * @throws ElementSavingFailedException
     */
    public function create(string $name, int $storeId): SubGroupConfig;

    /**
     * @throws NotFoundException
     * @throws InvalidArgumentException
     * @throws ElementSavingFailedException
     */
    public function update(int $id, string $name, ?string $description): SubGroupConfig;

    /**
     * Refused while a key assignment of the store still carries the entry, so that deleting a sub
     * group cannot silently untag the assignments naming it.
     *
     * The probe and the delete run as one unit of work behind the lock an assignment writer has to
     * take to tag anything with the entry, so an assignment committed while the delete is in flight
     * is either seen by the probe and refused, or arrives after the entry is gone.
     *
     * @throws NotFoundException
     * @throws ConflictException
     * @throws DatabaseException
     */
    public function delete(int $id): void;
}
