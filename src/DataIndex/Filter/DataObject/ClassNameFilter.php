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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\DataObject;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQuery;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Request\ClassNameParametersInterface;

/**
 * @internal
 */
final class ClassNameFilter implements FilterInterface
{
    /**
     * @throws Exception
     */
    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (
            !$parameters instanceof ClassNameParametersInterface ||
            !$query instanceof DataObjectQuery ||
            !$parameters->getClassName()
        ) {
            return $query;
        }

        return $query->setClassDefinitionName($parameters->getClassName());
    }
}
