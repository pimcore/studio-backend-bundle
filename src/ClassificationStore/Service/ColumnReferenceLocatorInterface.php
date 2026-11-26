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

/**
 * Finds the classification store references of a grid column set, and reads and writes the config they hold.
 *
 * A reference is a plain classification store column or the source field of an advanced column:
 * [column index, advanced column index or null, name of the classification store field, relation or null].
 *
 * @internal
 */
interface ColumnReferenceLocatorInterface
{
    public const string GROUP_IDENTIFIER = 'groupIdentifier';

    public const string KEY_IDENTIFIER = 'keyIdentifier';

    public const string GROUP_ID = 'groupId';

    public const string KEY_ID = 'keyId';

    /**
     * Every classification store reference of the columns, in column order.
     *
     * @param array<int, array> $columns
     *
     * @return array<int, array{0: int, 1: int|null, 2: string, 3?: string|null}>
     */
    public function references(array $columns): array;

    /**
     * The references whose config carries both identifiers.
     *
     * @param array<int, array> $columns
     *
     * @return array<int, array{0: int, 1: int|null, 2: string, 3?: string|null}>
     */
    public function identifierReferences(array $columns): array;

    /**
     * The references whose config carries an integer pair and not both identifiers.
     *
     * @param array<int, array> $columns
     *
     * @return array<int, array{0: int, 1: int|null, 2: string, 3?: string|null}>
     */
    public function pairReferences(array $columns): array;

    /**
     * The classification store column filters, as the columns they filter, keyed by their index in the filter.
     *
     * @param array<int, mixed> $columnFilters
     *
     * @return array<int, array>
     */
    public function filterColumns(array $columnFilters): array;

    /**
     * @param array<int, array> $columns
     * @param array{0: int, 1: int|null, 2: string, 3?: string|null} $reference
     */
    public function configAt(array $columns, array $reference): array;

    /**
     * @param array<int, array> $columns
     * @param array{0: int, 1: int|null, 2: string, 3?: string|null} $reference
     *
     * @return array<int, array>
     */
    public function replaceConfig(array $columns, array $reference, array $config): array;

    public function hasIdentifiers(array $config): bool;

    public function hasIdPair(array $config): bool;
}
