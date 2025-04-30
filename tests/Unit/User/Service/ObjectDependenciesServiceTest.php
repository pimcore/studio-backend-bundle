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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\DependencyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\ObjectDependenciesService;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class ObjectDependenciesServiceTest extends Unit
{
    public function testIfHiddenIsSet(): void
    {
        $demoObject = $this->makeEmpty(Concrete::class, [
            'isAllowed' => false,
        ]);

        $dataObjectServiceResolver = $this->makeEmpty(DataObjectServiceResolverInterface::class, [
            'getObjectsReferencingUser' => [$demoObject],
        ]);
        $dependencyHydrator = $this->makeEmpty(DependencyHydratorInterface::class);

        $objectDependenciesService = new ObjectDependenciesService($dataObjectServiceResolver, $dependencyHydrator);

        $user = $this->makeEmpty(UserInterface::class, [
            'getId' => 1,
        ]);

        $objectDependencies = $objectDependenciesService->getDependenciesForUser($user);

        $this->assertTrue($objectDependencies->isHasHidden());
    }
}
