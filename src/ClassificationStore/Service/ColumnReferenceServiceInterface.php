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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\ClassificationStoreConfig;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Collection\ColumnCollection;

/**
 * Translates the classification store references of grid columns between integer ids and identifiers.
 *
 * @internal
 */
interface ColumnReferenceServiceInterface
{
    /**
     * The reference the identifiers of the config name, resolved in the store of the classification store
     * field `$fieldName` of the class. Null when the config does not carry both identifiers.
     *
     * @throws InvalidArgumentException when the field is no classification store field, or an identifier does
     *                                 not resolve
     * @throws DatabaseException
     */
    public function resolve(string $classId, string $fieldName, array $config): ?ClassificationStoreConfig;

    /**
     * Reads the identifiers of the columns in at most one statement per entity type and store, so that the
     * later resolve() calls of the same columns issue none. Priming the same columns for another class keeps
     * what was read: the memo is keyed by store, and a class-unscoped search whose rows alternate between
     * classes sharing a store re-primes per row but reads a shared reference once.
     *
     * @throws DatabaseException
     */
    public function prime(string $classId, ColumnCollection $columns): void;

    /**
     * Reads the identifiers of the classification store column filters in at most one statement per entity
     * type and store, so that the filters applied afterwards issue none.
     *
     * @param array<int, mixed> $columnFilters as carried by the filter parameter
     *
     * @throws DatabaseException
     */
    public function primeFilters(string $classId, array $columnFilters): void;

    /**
     * Columns as stored, with `groupIdentifier`/`keyIdentifier` written from the integer pair of every
     * classification store reference. A reference whose entity has no identifier keeps the pair alone.
     *
     * @param array<int, array> $columns
     *
     * @return array<int, array>
     *
     * @throws DatabaseException
     */
    public function addIdentifiers(string $classId, array $columns): array;

    /**
     * Columns as served, with the integer pair of every reference re-derived from its identifiers
     * wherever those resolve in this installation.
     *
     * @param array<int, array> $columns
     *
     * @return array<int, array>
     *
     * @throws DatabaseException
     */
    public function deriveIds(string $classId, array $columns): array;

    /**
     * A saved filter as stored, with the identifiers written into the value of every classification store
     * column filter, under the rule of addIdentifiers().
     *
     * @throws DatabaseException
     */
    public function addFilterIdentifiers(string $classId, array $filter): array;

    /**
     * A saved filter as served, with the integer pair of every classification store column filter
     * re-derived under the rule of deriveIds().
     *
     * @throws DatabaseException
     */
    public function deriveFilterIds(string $classId, array $filter): array;
}
