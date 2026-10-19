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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Repository;

use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;

/**
 * @internal
 */
interface RelatedElementRepositoryInterface
{
    /**
     * Reads the data shown for relation targets straight from the element tables, without loading
     * the elements. Ids that no longer exist are missing from the result.
     *
     * @param string $elementType core element type: object, asset or document
     * @param int[] $ids
     *
     * @return array<int, RelatedElementData> keyed by element id
     *
     * @throws DatabaseException
     */
    public function getRelatedElementsData(string $elementType, array $ids): array;
}
