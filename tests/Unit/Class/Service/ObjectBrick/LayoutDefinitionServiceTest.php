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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Class\Service\ObjectBrick;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\Objectbrick\DefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\ConfigLayoutDefinitionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\ObjectBrick\LayoutDefinitionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ObjectBrickRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ObjectBrick\LayoutDefinition;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\ObjectBrick\LayoutDefinitionService;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Objectbrick\Definition as ObjectBrickDefinition;
use Pimcore\Model\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class LayoutDefinitionServiceTest extends Unit
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

        $brickField = $this->createMock(Objectbricks::class);
        $brickField->method('getName')->willReturn('bricks');
        $brickField->method('getAllowedTypes')->willReturn(['BrickA', 'BrickB']);

        $classDefinition = $this->createMock(ClassDefinition::class);
        $classDefinition->method('getFieldDefinitions')->willReturn(['bricks' => $brickField]);

        $classDefinitionResolver = $this->createMock(ClassDefinitionResolverInterface::class);
        $classDefinitionResolver->expects($this->once())
            ->method('getById')
            ->with(self::CLASS_ID)
            ->willReturn($classDefinition);

        $definitionResolver = $this->createMock(DefinitionResolverInterface::class);
        $definitionResolver->method('getByKey')->willReturnCallback(
            function (string $key): ObjectBrickDefinition {
                $definition = $this->createMock(ObjectBrickDefinition::class);
                $definition->method('getKey')->willReturn($key);

                return $definition;
            }
        );

        $hydrator = $this->createMock(LayoutDefinitionHydratorInterface::class);
        $hydrator->method('hydrate')->willReturnCallback(
            static fn (ObjectBrickDefinition $definition): LayoutDefinition
                => new LayoutDefinition($definition->getKey(), 'panel')
        );

        $dataObjectServiceResolver = $this->createMock(DataObjectServiceResolverInterface::class);
        $dataObjectServiceResolver->expects($this->exactly(2))->method('enrichLayoutDefinition');

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->exactly(2))->method('dispatch');

        $service = $this->createService(
            $user,
            $dataObject,
            $classDefinitionResolver,
            $definitionResolver,
            $hydrator,
            $dataObjectServiceResolver,
            $eventDispatcher
        );

        $result = $service->getLayoutDefinitionsForObject(self::OBJECT_ID);

        $this->assertCount(2, $result);
        $this->assertSame('BrickA', $result[0]->getKey());
        $this->assertSame('BrickB', $result[1]->getKey());
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
        ClassDefinitionResolverInterface $classDefinitionResolver,
        ?DefinitionResolverInterface $definitionResolver = null,
        ?LayoutDefinitionHydratorInterface $hydrator = null,
        ?DataObjectServiceResolverInterface $dataObjectServiceResolver = null,
        ?EventDispatcherInterface $eventDispatcher = null,
    ): LayoutDefinitionService {
        $securityService = $this->createMock(SecurityServiceInterface::class);
        $securityService->method('getCurrentUser')->willReturn($user);

        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver->method('getElementById')->with('object', self::OBJECT_ID)->willReturn($dataObject);

        return new LayoutDefinitionService(
            $serviceResolver,
            $securityService,
            $dataObjectServiceResolver ?? $this->createMock(DataObjectServiceResolverInterface::class),
            $classDefinitionResolver,
            $definitionResolver ?? $this->createMock(DefinitionResolverInterface::class),
            $hydrator ?? $this->createMock(LayoutDefinitionHydratorInterface::class),
            $this->createMock(ObjectBrickRepositoryInterface::class),
            $this->createMock(ConfigLayoutDefinitionHydratorInterface::class),
            $eventDispatcher ?? $this->createMock(EventDispatcherInterface::class),
        );
    }
}
