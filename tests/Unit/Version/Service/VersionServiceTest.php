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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Version\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Version\Hydrator\VersionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Version\Publisher\DataObjectVersionPublisherInterface;
use Pimcore\Bundle\StudioBackendBundle\Version\Repository\VersionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Version\Service\VersionService;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;
use Pimcore\Model\Version;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @internal
 */
final class VersionServiceTest extends Unit
{
    private const int VERSION_ID = 42;

    private const int ELEMENT_ID = 5;

    /**
     * The version is published in place: the publisher gets the version to write into, and the published
     * version is the version itself, not whatever version happens to be the newest one afterwards.
     */
    public function testPublishVersionPublishesTheVersionItself(): void
    {
        $user = $this->createMock(UserInterface::class);
        $version = $this->createVersion();
        $versionElement = $this->createMock(Concrete::class);
        $versionElement->method('getId')->willReturn(self::ELEMENT_ID);
        $currentElement = $this->createMock(Concrete::class);

        $repository = $this->createMock(VersionRepositoryInterface::class);
        $repository->expects($this->once())->method('getVersionById')->with(self::VERSION_ID)->willReturn($version);
        $repository->expects($this->once())
            ->method('getElementFromVersion')
            ->with($version, $user)
            ->willReturn($versionElement);
        $repository->expects($this->never())->method('getLastVersion');

        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver->expects($this->once())
            ->method('getElementById')
            ->with('object', self::ELEMENT_ID)
            ->willReturn($currentElement);

        $securityService = $this->createMock(SecurityServiceInterface::class);
        $securityService->expects($this->once())
            ->method('hasElementPermission')
            ->with($currentElement, $user, ElementPermissions::PUBLISH_PERMISSION);

        $publisher = $this->createMock(DataObjectVersionPublisherInterface::class);
        $publisher->expects($this->once())->method('publish')->with($versionElement, $user, $version);

        $service = $this->createService($repository, $serviceResolver, $securityService, $publisher);

        $this->assertSame(self::VERSION_ID, $service->publishVersion(self::VERSION_ID, $user));
    }

    /**
     * An auto-save version is rewritten by the editor of its user at any time, it is published as a new version, so
     * the published version is the newest one
     */
    public function testPublishVersionOfAnAutoSaveVersionReturnsTheNewVersion(): void
    {
        $user = $this->createMock(UserInterface::class);
        $version = $this->createAutoSaveVersion();
        $versionElement = $this->createMock(Concrete::class);
        $versionElement->method('getId')->willReturn(self::ELEMENT_ID);
        $newVersion = $this->createVersion(self::VERSION_ID + 1);

        $repository = $this->createMock(VersionRepositoryInterface::class);
        $repository->method('getVersionById')->willReturn($version);
        $repository->method('getElementFromVersion')->willReturn($versionElement);
        $repository->expects($this->once())
            ->method('getLastVersion')
            ->with(self::ELEMENT_ID, 'object', $user)
            ->willReturn($newVersion);

        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver->method('getElementById')->willReturn($this->createMock(Concrete::class));

        $publisher = $this->createMock(DataObjectVersionPublisherInterface::class);
        $publisher->expects($this->once())->method('publish')->with($versionElement, $user, $version);

        $service = $this->createService(
            $repository,
            $serviceResolver,
            $this->createMock(SecurityServiceInterface::class),
            $publisher
        );

        $this->assertSame(self::VERSION_ID + 1, $service->publishVersion(self::VERSION_ID, $user));
    }

    public function testPublishVersionRejectsElementsWithoutPublisher(): void
    {
        $user = $this->createMock(UserInterface::class);
        $version = $this->createVersion();
        $versionElement = $this->createMock(Concrete::class);
        $versionElement->method('getId')->willReturn(self::ELEMENT_ID);

        $repository = $this->createMock(VersionRepositoryInterface::class);
        $repository->method('getVersionById')->willReturn($version);
        $repository->method('getElementFromVersion')->willReturn($versionElement);

        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver->method('getElementById')->willReturn($this->createMock(Concrete::class));

        $service = $this->createService(
            $repository,
            $serviceResolver,
            $this->createMock(SecurityServiceInterface::class),
            null
        );

        $this->expectException(InvalidElementTypeException::class);
        $service->publishVersion(self::VERSION_ID, $user);
    }

    private function createService(
        VersionRepositoryInterface $repository,
        ServiceResolverInterface $serviceResolver,
        SecurityServiceInterface $securityService,
        ?DataObjectVersionPublisherInterface $publisher
    ): VersionService {
        $locator = $this->createMock(ServiceProviderInterface::class);
        $locator->expects($this->any())->method('has')->with(DataObject::class)->willReturn($publisher !== null);
        $locator->expects($this->any())->method('get')->with(DataObject::class)->willReturn($publisher);

        return new VersionService(
            $this->createMock(EventDispatcherInterface::class),
            $repository,
            $serviceResolver,
            $locator,
            $this->createMock(VersionHydratorInterface::class),
            $securityService
        );
    }

    private function createAutoSaveVersion(): Version
    {
        $version = $this->createVersion();
        $version->setAutoSave(true);

        return $version;
    }

    private function createVersion(int $id = self::VERSION_ID): Version
    {
        // Version's constructor pulls its services from the container
        /** @var Version $version */
        $version = (new ReflectionClass(Version::class))->newInstanceWithoutConstructor();
        $version->setId($id);

        return $version;
    }
}
