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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Class\Service;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\Objectbrick\DefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\ConfigLayoutDefinitionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\ObjectBrick\LayoutDefinitionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ObjectBrickRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\ObjectBrick\LayoutDefinitionService;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataObjectServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The object layout route only requires the 'objects' permission,
 * so the service has to enforce view permission on the requested object on its own.
 *
 * @internal
 */
final class ObjectBrickLayoutDefinitionServiceTest extends Unit
{
    private const OBJECT_ID = 42;

    private const CLASS_ID = 'CAR';

    /**
     * @throws Exception
     */
    public function testUserWithoutViewPermissionOnObjectIsRejected(): void
    {
        $user = $this->createMock(UserInterface::class);

        $dataObjectService = $this->createMock(DataObjectServiceInterface::class);
        $dataObjectService->method('getDataObjectElement')
            ->with($user, self::OBJECT_ID)
            ->willThrowException(new ForbiddenException('You dont have view permission'));

        $classDefinitionResolver = $this->createMock(ClassDefinitionResolverInterface::class);
        $classDefinitionResolver->expects($this->never())->method('getById');

        $service = $this->createService($user, $dataObjectService, $classDefinitionResolver);

        $this->expectException(ForbiddenException::class);

        $service->getLayoutDefinitionsForObject(self::OBJECT_ID);
    }

    /**
     * @throws Exception
     */
    public function testUserWithViewPermissionOnObjectGetsLayoutDefinitions(): void
    {
        $user = $this->createMock(UserInterface::class);

        $dataObject = $this->createMock(Concrete::class);
        $dataObject->method('getClassId')->willReturn(self::CLASS_ID);

        $dataObjectService = $this->createMock(DataObjectServiceInterface::class);
        $dataObjectService->expects($this->once())
            ->method('getDataObjectElement')
            ->with($user, self::OBJECT_ID)
            ->willReturn($dataObject);

        // A class without object brick fields: the permission check passes and nothing has to be resolved.
        $classDefinitionResolver = $this->createMock(ClassDefinitionResolverInterface::class);
        $classDefinitionResolver->expects($this->once())
            ->method('getById')
            ->with(self::CLASS_ID)
            ->willReturn(new ClassDefinition());

        $service = $this->createService($user, $dataObjectService, $classDefinitionResolver);

        $this->assertSame([], $service->getLayoutDefinitionsForObject(self::OBJECT_ID));
    }

    private function createService(
        UserInterface $user,
        DataObjectServiceInterface $dataObjectService,
        ClassDefinitionResolverInterface $classDefinitionResolver
    ): LayoutDefinitionService {
        $securityService = $this->createMock(SecurityServiceInterface::class);
        $securityService->method('getCurrentUser')->willReturn($user);

        return new LayoutDefinitionService(
            $dataObjectService,
            $securityService,
            $this->createMock(DataObjectServiceResolverInterface::class),
            $classDefinitionResolver,
            $this->createMock(DefinitionResolverInterface::class),
            $this->createMock(LayoutDefinitionHydratorInterface::class),
            $this->createMock(ObjectBrickRepositoryInterface::class),
            $this->createMock(ConfigLayoutDefinitionHydratorInterface::class),
            $this->createMock(EventDispatcherInterface::class),
        );
    }
}
