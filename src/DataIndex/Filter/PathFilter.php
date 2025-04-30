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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\PathParameterInterface;

/**
 * @internal
 */
final class PathFilter implements FilterInterface
{
    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (!$parameters instanceof PathParameterInterface) {
            return $query;
        }

        $path = $parameters->getPath();
        $includeParent = $parameters->getPathIncludeParent();
        $includeDescendants = $parameters->getPathIncludeDescendants();

        if (!$path) {
            return $query;
        }

        return $query->filterPath($path, $includeDescendants, $includeParent);
    }
}
