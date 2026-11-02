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
     * @throws NotFoundException
     * @throws ConflictException
     * @throws DatabaseException
     */
    public function delete(int $id): void;
}
