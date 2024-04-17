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

use Pimcore\Bundle\StudioApiBundle\Request\Query\Filter\ParametersInterface;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\QueryInterface;

/**
 * @internal
 */
final class PathFilter implements FilterInterface
{
    public function apply(ParametersInterface $parameters, QueryInterface $query): QueryInterface
    {
        $path = $parameters->getPath();
        $includeParent = $parameters->getPathIncludeParent();
        $includeDescendants = $parameters->getPathIncludeDescendants();

        if (!$path) {
            return $query;
        }

        return $query->filterPath($path, $includeDescendants, $includeParent);
    }
}
