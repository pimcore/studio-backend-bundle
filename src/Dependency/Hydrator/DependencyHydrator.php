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