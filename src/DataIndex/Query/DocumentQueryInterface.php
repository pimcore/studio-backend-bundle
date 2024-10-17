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
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Document\DocumentSearchInterface;

/**
 * @internal
 */
interface DocumentQueryInterface extends QueryInterface
{
    public function orderByField(string $fieldName, SortDirection $direction): self;

    public function wildcardSearch(
        string $fieldName,
        string $searchTerm,
        bool $enablePqlFieldNameResolution = true
    ): self;

    public function getSearch(): DocumentSearchInterface;
}
