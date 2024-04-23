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

namespace Pimcore\Bundle\StudioApiBundle\Filter;

use Pimcore\Bundle\StudioApiBundle\Request\Query\Filter\CollectionParametersInterface;
use Pimcore\Bundle\StudioApiBundle\Request\Query\Filter\ElementParametersInterface;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\QueryInterface;

/**
 * @internal
 */
final class ParentIdFilter implements FilterInterface
{
    public function apply(CollectionParametersInterface $parameters, QueryInterface $query): QueryInterface
    {
        if (!$parameters instanceof ElementParametersInterface) {
            return $query;
        }

        $parentId = $parameters->getParentId();

        if(!$parentId) {
            return $query;
        }

        return $query->filterParentId($parentId);
    }
}
