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

namespace Pimcore\Bundle\StudioApiBundle\Filter\DataObject;

use Pimcore\Bundle\StudioApiBundle\Filter\FilterInterface;
use Pimcore\Bundle\StudioApiBundle\Request\Query\Filter\DataObjectParametersInterface;
use Pimcore\Bundle\StudioApiBundle\Request\Query\Filter\ParametersInterface;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\DataObjectQuery;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\QueryInterface;

/**
 * @internal
 */
final class ClassNameFilter implements FilterInterface
{
    public function apply(ParametersInterface $parameters, QueryInterface $query): QueryInterface
    {
        if(
            !$parameters instanceof DataObjectParametersInterface ||
            !$query instanceof DataObjectQuery ||
            !$parameters->getClassName()
        ) {
            return $query;
        }

        return $query->setClassDefinitionName($parameters->getClassName());
    }
}
