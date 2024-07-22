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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ExcludeFolderParameterInterface;

/**
 * @internal
 */
final class ExcludeFolderFilter implements FilterInterface
{
    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (!$parameters instanceof ExcludeFolderParameterInterface) {
            return $query;
        }

        $excludeFolders = $parameters->getExcludeFolders();
        if (!$excludeFolders) {
            return $query;
        }

        return $query->excludeFolders();
    }
}
