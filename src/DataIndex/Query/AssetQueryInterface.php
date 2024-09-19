<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Query;

use Pimcore\Bundle\GenericDataIndexBundle\Enum\Search\SortDirection;

/**
 * @internal
 */
interface AssetQueryInterface extends QueryInterface
{
    public function filterMetadata(string $name, string $type, mixed $data): self;

    public function orderByField(string $fieldName, SortDirection $direction): self;

    public function wildcardSearch(
        string $fieldName,
        string $searchTerm,
        bool $enablePqlFieldNameResolution = true
    ): self;

    public function filterDatetime(
        string $field,
        int|null $startDate = null,
        int|null $endDate = null,
        int|null $onDate = null,
        bool $roundToDay = true,
        bool $enablePqlFieldNameResolution = true
    ): self;
}
