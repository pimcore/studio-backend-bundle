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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\DataObject;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ClassIdsParameterInterface;

/**
 * @internal
 */
final class ClassIdsFilter implements FilterInterface
{
    /**
     * @throws Exception
     */
    public function apply(mixed $parameters, QueryInterface $query): QueryInterface
    {
        if (
            !$parameters instanceof ClassIdsParameterInterface ||
            !$query instanceof DataObjectQueryInterface
        ) {
            return $query;
        }

        $classIds = $parameters->getClassIdsArray();
        if ($classIds === null || $classIds === []) {
            return $query;
        }

        return $query->setClassDefinitionIds($classIds);
    }
}
