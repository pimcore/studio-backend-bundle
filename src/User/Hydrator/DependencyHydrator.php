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

namespace Pimcore\Bundle\StudioBackendBundle\User\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\User\Schema\Dependency;
use Pimcore\Model\DataObject\Concrete;

/**
 * @internal
 */
final class DependencyHydrator implements DependencyHydratorInterface
{
    public function hydrate(Concrete $concrete): Dependency
    {
        return new Dependency(
            id: $concrete->getId(),
            path: $concrete->getRealFullPath(),
            subtype: $concrete->getClass()->getName(),
        );
    }
}
