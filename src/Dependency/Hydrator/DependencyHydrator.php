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

namespace Pimcore\Bundle\StudioBackendBundle\Dependency\Hydrator;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\ElementSearchResultItemInterface;
use Pimcore\Bundle\StudioBackendBundle\Dependency\Schema\Dependency;

/**
 * @internal
 */
final readonly class DependencyHydrator implements DependencyHydratorInterface
{
    public function hydrate(ElementSearchResultItemInterface $dependency): Dependency
    {
        // isPublished does not exist in the ElementSearchResultItemInterface
        // unfortunately there is no other interface for is published
        // documents and objects have the isPublished method
        return new Dependency(
            $dependency->getId(),
            $dependency->getFullPath(),
            $dependency->getElementType()->value,
            $dependency->getType(),
            method_exists($dependency, 'isPublished') ? $dependency->isPublished() : true,
        );
    }
}
