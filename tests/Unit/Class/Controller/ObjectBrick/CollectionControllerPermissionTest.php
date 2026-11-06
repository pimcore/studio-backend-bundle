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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Class\Controller\ObjectBrick;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Class\Controller\ObjectBrick\CollectionController;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\ObjectBrick\ObjectBrickServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Security\PermissionsToCheck;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * The object brick collection route is used by the class definition editor and the object brick editor,
 * so it requires one of the 'classes' or 'objectbricks' permissions.
 *
 * @internal
 */
final class CollectionControllerPermissionTest extends Unit
{
    public function testUserWithoutAnyOfTheRequiredPermissionsIsRejected(): void
    {
        $service = $this->createMock(ObjectBrickServiceInterface::class);
        $service->expects($this->never())->method('listObjectBricks');

        $controller = $this->createController($service, false);

        $this->expectException(AccessDeniedException::class);

        $controller->getObjectBrickCollection();
    }

    public function testUserWithOneOfTheRequiredPermissionsGetsTheCollection(): void
    {
        $service = $this->createMock(ObjectBrickServiceInterface::class);
        $service->expects($this->once())->method('listObjectBricks')->willReturn(new Collection(0, []));

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->method('serialize')->willReturn('{"totalItems":0,"items":[]}');

        $controller = $this->createController($service, true, $serializer);

        $this->assertSame(Response::HTTP_OK, $controller->getObjectBrickCollection()->getStatusCode());
    }

    private function createController(
        ObjectBrickServiceInterface $service,
        bool $granted,
        ?SerializerInterface $serializer = null
    ): CollectionController {
        $checker = $this->createMock(AuthorizationCheckerInterface::class);
        $checker->method('isGranted')->willReturnCallback(
            function (mixed $attribute, mixed $subject = null) use ($granted): bool {
                $this->assertSame('HasOneOf', $attribute);
                $this->assertInstanceOf(PermissionsToCheck::class, $subject);
                $this->assertEqualsCanonicalizing(
                    [UserPermissions::CLASS_DEFINITION->value, UserPermissions::OBJECT_BRICKS->value],
                    $subject->getPermissionsToCheck()
                );

                return $granted;
            }
        );

        $container = new Container();
        $container->set('security.authorization_checker', $checker);

        $controller = new CollectionController($serializer ?? $this->createMock(SerializerInterface::class), $service);
        $controller->setContainer($container);

        return $controller;
    }
}
