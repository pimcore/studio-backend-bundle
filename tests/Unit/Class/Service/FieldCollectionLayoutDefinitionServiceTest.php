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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\FieldCollection\DefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\ConfigLayoutDefinitionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\FieldCollection\LayoutDefinitionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\FieldCollectionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\FieldCollection\LayoutDefinitionService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\ActiveGroupTitleEnricherInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The object layout route only requires the 'objects' permission,
 * so the service has to enforce view permission on the requested object on its own.
 *
 * @internal
 */
final class FieldCollectionLayoutDefinitionServiceTest extends Unit
{
    private const OBJECT_ID = 42;

    private const CLASS_ID = 'CAR';

    /**
     * @throws Exception
     */
    public function testUserWithoutViewPermissionOnObjectIsRejected(): void
    {
        $user = new User();
        $dataObject = $this->createDataObject($user, view: false, list: true);

        $classDefinitionResolver = $this->createMock(ClassDefinitionResolverInterface::class);
        $classDefinitionResolver->expects($this->never())->method('getById');

        $service = $this->createService($user, $dataObject, $classDefinitionResolver);

        $this->expectException(ForbiddenException::class);

        $service->getLayoutDefinitionsForObject(self::OBJECT_ID);
    }

    /**
     * @throws Exception
     */
    public function testObjectTheUserMayNotListIsReportedAsNotFound(): void
    {
        $user = new User();
        $dataObject = $this->createDataObject($user, view: false, list: false);

        $classDefinitionResolver = $this->createMock(ClassDefinitionResolverInterface::class);
        $classDefinitionResolver->expects($this->never())->method('getById');

        $service = $this->createService($user, $dataObject, $classDefinitionResolver);

        $this->expectException(NotFoundException::class);

        $service->getLayoutDefinitionsForObject(self::OBJECT_ID);
    }

    /**
     * @throws Exception
     */
    public function testUserWithViewPermissionOnObjectGetsLayoutDefinitions(): void
    {
        $user = new User();
        $dataObject = $this->createDataObject($user, view: true, list: true);

        // A class without field collection fields: the permission check passes and nothing has to be resolved.
        $classDefinitionResolver = $this->createMock(ClassDefinitionResolverInterface::class);
        $classDefinitionResolver->expects($this->once())
            ->method('getById')
            ->with(self::CLASS_ID)
            ->willReturn(new ClassDefinition());

        $service = $this->createService($user, $dataObject, $classDefinitionResolver);

        $this->assertSame([], $service->getLayoutDefinitionsForObject(self::OBJECT_ID));
    }

    private function createDataObject(User $user, bool $view, bool $list): Concrete
    {
        $dataObject = $this->createMock(Concrete::class);
        $dataObject->method('getClassId')->willReturn(self::CLASS_ID);
        $dataObject->method('isAllowed')->willReturnCallback(
            static fn (string $type, ?User $checkedUser = null): bool => $checkedUser === $user
                && match ($type) {
                    ElementPermissions::VIEW_PERMISSION => $view,
                    ElementPermissions::LIST_PERMISSION => $list,
                    default => false,
                }
        );

        return $dataObject;
    }

    private function createService(
        User $user,
        Concrete $dataObject,
        ClassDefinitionResolverInterface $classDefinitionResolver
    ): LayoutDefinitionService {
        $securityService = $this->createMock(SecurityServiceInterface::class);
        $securityService->method('getCurrentUser')->willReturn($user);

        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver->method('getElementById')->with('object', self::OBJECT_ID)->willReturn($dataObject);

        return new LayoutDefinitionService(
            $serviceResolver,
            $securityService,
            $this->createMock(DataObjectServiceResolverInterface::class),
            $classDefinitionResolver,
            $this->createMock(DefinitionResolverInterface::class),
            $this->createMock(FieldCollectionRepositoryInterface::class),
            $this->createMock(LayoutDefinitionHydratorInterface::class),
            $this->createMock(ConfigLayoutDefinitionHydratorInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(ActiveGroupTitleEnricherInterface::class),
        );
    }
}
