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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\DependencyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\ObjectDependencies;
use Pimcore\Bundle\StudioBackendBundle\User\Service\ObjectDependenciesService;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
#[CoversClass(ObjectDependenciesService::class)]
#[UsesClass(ObjectDependencies::class)]
final class ObjectDependenciesServiceTest extends TestCase
{
    public function testIfHiddenIsSet(): void
    {
        $demoObject = $this->createMock(Concrete::class);
        $demoObject->method('isAllowed')->willReturn(false);

        $dataObjectServiceResolver = $this->createMock(DataObjectServiceResolverInterface::class);
        $dataObjectServiceResolver->method('getObjectsReferencingUser')->willReturn([$demoObject]);

        $dependencyHydrator = $this->createMock(DependencyHydratorInterface::class);

        $objectDependenciesService = new ObjectDependenciesService($dataObjectServiceResolver, $dependencyHydrator);

        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn(1);

        $objectDependencies = $objectDependenciesService->getDependenciesForUser($user);

        $this->assertTrue($objectDependencies->isHasHidden());
    }
}
