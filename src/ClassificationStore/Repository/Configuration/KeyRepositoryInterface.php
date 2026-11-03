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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyUpdate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactKeyEntry;
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
     * One page of the store's keys, ordered by identifier, so that a configuration transfer never
     * holds a whole store at once.
     *
     * `$disabledOnly` is the state the page covers: the listing's own `includeDisabled` narrows to
     * `(enabled is null or enabled = 0)` rather than widening to every row, so full coverage of a
     * store is the two walks together.
     *
     * @return KeyConfig[]
     */
    public function getChunkByStore(int $storeId, bool $disabledOnly, int $limit, int $offset): array;

    /**
     * One page of the store's keys in the given state, taken after the given row id, which is the
     * paging an apply walks the target in - it writes while it walks, and writing `enabled` moves a
     * key out of the state its walk covers, which an offset into that walk would then skip past.
     *
     * @return KeyConfig[]
     */
    public function getChunkAfterId(int $storeId, bool $disabledOnly, int $afterId, int $limit): array;

    /**
     * Writes the artifact's key entry onto the given configuration, or onto a new one when there is
     * none, stamping the identifier on a row that does not carry one yet.
     *
     * @throws ElementSavingFailedException
     */
    public function saveFromArtifact(?KeyConfig $config, int $storeId, ArtifactKeyEntry $entry): KeyConfig;

    /**
     * @throws ElementSavingFailedException
     */
    public function create(string $name, int $storeId, ?string $title = null): KeyConfig;

    /**
     * The title and the description are written only when the payload supplied them, a supplied
     * empty one clearing the stored value; the definition is written only when it is not null.
     *
     * @throws NotFoundException
     * @throws ElementSavingFailedException
     */
    public function update(int $id, KeyUpdate $parameters): KeyConfig;

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
