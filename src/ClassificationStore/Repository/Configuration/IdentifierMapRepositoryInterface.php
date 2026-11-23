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

/**
 * Translates group and key ids to their stored identifiers and back.
 *
 * Separate from the group and key repositories on purpose: a caller that only maps identifiers -
 * a saved grid column, a filter - has no use for their listings, and reaching the mapping through
 * them pulls the listing search and the translations it reads along with it.
 *
 * @internal
 */
interface IdentifierMapRepositoryInterface
{
    /**
     * @param int[] $ids
     *
     * @return array<int, string> the identifier of each group that carries one, by id
     *
     * @throws DatabaseException
     */
    public function getGroupIdentifiersByIds(int $storeId, array $ids): array;

    /**
     * @param string[] $identifiers
     *
     * @return array<string, int> the id of each group found, by identifier
     *
     * @throws DatabaseException
     */
    public function getGroupIdsByIdentifiers(int $storeId, array $identifiers): array;

    /**
     * @param int[] $ids
     *
     * @return array<int, string> the identifier of each key that carries one, by id
     *
     * @throws DatabaseException
     */
    public function getKeyIdentifiersByIds(int $storeId, array $ids): array;

    /**
     * @param string[] $identifiers
     *
     * @return array<string, int> the id of each key found, by identifier
     *
     * @throws DatabaseException
     */
    public function getKeyIdsByIdentifiers(int $storeId, array $identifiers): array;
}
