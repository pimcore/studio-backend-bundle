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
use Pimcore\Model\Element\AbstractElement;
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

    private const int ELEMENT_VERSION_COUNT = 10;

    private const int ELEMENT_MODIFICATION_DATE = 1700000000;

    /**
     * A prepared version (newer than the element) is published in place: the publisher gets the version to write
     * into, and the published version is the version itself, not whatever version happens to be the newest one
     * afterwards.
     */
    public function testPublishVersionPublishesAPreparedVersionItself(): void
    {
        if (!self::coreSupportsPublishingInPlace()) {
            $this->markTestSkipped('The installed pimcore/pimcore does not support publishing a version in place');
        }

        $user = $this->createMock(UserInterface::class);
        $version = $this->createVersion(versionCount: self::ELEMENT_VERSION_COUNT + 1, date: self::ELEMENT_MODIFICATION_DATE + 60);
        $versionElement = $this->createMock(Concrete::class);
        $versionElement->method('getId')->willReturn(self::ELEMENT_ID);
        $currentElement = $this->createCurrentElement();

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
     * With a core that doesn't support publishing in place (pimcore/pimcore < 12.3.28), a prepared version is
     * published as a new version, as before, and the published version is the newest one
     */
    public function testPublishVersionPublishesAPreparedVersionAsANewVersionWithAnOlderCore(): void
    {
        if (self::coreSupportsPublishingInPlace()) {
            $this->markTestSkipped('The installed pimcore/pimcore supports publishing a version in place');
        }

        $user = $this->createMock(UserInterface::class);
        $version = $this->createVersion(versionCount: self::ELEMENT_VERSION_COUNT + 1, date: self::ELEMENT_MODIFICATION_DATE + 60);
        $versionElement = $this->createMock(Concrete::class);
        $versionElement->method('getId')->willReturn(self::ELEMENT_ID);

        $repository = $this->createMock(VersionRepositoryInterface::class);
        $repository->method('getVersionById')->willReturn($version);
        $repository->method('getElementFromVersion')->willReturn($versionElement);
        $repository->expects($this->once())
            ->method('getLastVersion')
            ->with(self::ELEMENT_ID, 'object', $user)
            ->willReturn($this->createVersion(self::VERSION_ID + 1));

        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver->method('getElementById')->willReturn($this->createCurrentElement());

        $publisher = $this->createMock(DataObjectVersionPublisherInterface::class);
        $publisher->expects($this->once())->method('publish')->with($versionElement, $user, null);

        $service = $this->createService(
            $repository,
            $serviceResolver,
            $this->createMock(SecurityServiceInterface::class),
            $publisher
        );

        $this->assertSame(self::VERSION_ID + 1, $service->publishVersion(self::VERSION_ID, $user));
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
        $publisher->expects($this->once())->method('publish')->with($versionElement, $user, null);

        $service = $this->createService(
            $repository,
            $serviceResolver,
            $this->createMock(SecurityServiceInterface::class),
            $publisher
        );

        $this->assertSame(self::VERSION_ID + 1, $service->publishVersion(self::VERSION_ID, $user));
    }

    /**
     * Publishing an older version is a rollback: it is published as a new version, so the history keeps the older
     * version as it was, and the published version is the newest one
     */
    public function testPublishVersionOfAnOlderVersionReturnsTheNewVersion(): void
    {
        $user = $this->createMock(UserInterface::class);
        $version = $this->createVersion(versionCount: self::ELEMENT_VERSION_COUNT - 2, date: self::ELEMENT_MODIFICATION_DATE - 60);
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
        $serviceResolver->method('getElementById')->willReturn($this->createCurrentElement());

        $publisher = $this->createMock(DataObjectVersionPublisherInterface::class);
        $publisher->expects($this->once())->method('publish')->with($versionElement, $user, null);

        $service = $this->createService(
            $repository,
            $serviceResolver,
            $this->createMock(SecurityServiceInterface::class),
            $publisher
        );

        $this->assertSame(self::VERSION_ID + 1, $service->publishVersion(self::VERSION_ID, $user));
    }

    public function versionSelectionProvider(): array
    {
        return [
            'newer version count only' => [self::ELEMENT_VERSION_COUNT + 1, self::ELEMENT_MODIFICATION_DATE, false, true],
            'newer date only' => [self::ELEMENT_VERSION_COUNT, self::ELEMENT_MODIFICATION_DATE + 60, false, true],
            'same version count and date as the element' => [self::ELEMENT_VERSION_COUNT, self::ELEMENT_MODIFICATION_DATE, false, false],
            'auto-save version newer than the element' => [self::ELEMENT_VERSION_COUNT + 1, self::ELEMENT_MODIFICATION_DATE + 60, true, false],
        ];
    }

    /**
     * A version is published in place when it is newer than the element in its version count or its date (the
     * draft rule of the core's getLatestVersion()), but never an auto-save version; any other version is published
     * as a new version and the newest version is returned
     *
     * @dataProvider versionSelectionProvider
     */
    public function testPublishVersionSelectsTheVersionToWriteInto(
        int $versionCount,
        int $date,
        bool $autoSave,
        bool $publishedInPlace
    ): void {
        // an older core publishes every version as a new version
        $publishedInPlace = $publishedInPlace && self::coreSupportsPublishingInPlace();
        $user = $this->createMock(UserInterface::class);
        $version = $this->createVersion(versionCount: $versionCount, date: $date);
        $version->setAutoSave($autoSave);
        $versionElement = $this->createMock(Concrete::class);
        $versionElement->method('getId')->willReturn(self::ELEMENT_ID);

        $repository = $this->createMock(VersionRepositoryInterface::class);
        $repository->method('getVersionById')->willReturn($version);
        $repository->method('getElementFromVersion')->willReturn($versionElement);
        $repository->expects($publishedInPlace ? $this->never() : $this->once())
            ->method('getLastVersion')
            ->willReturn($this->createVersion(self::VERSION_ID + 1));

        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver->method('getElementById')->willReturn($this->createCurrentElement());

        $publisher = $this->createMock(DataObjectVersionPublisherInterface::class);
        $publisher->expects($this->once())
            ->method('publish')
            ->with($versionElement, $user, $publishedInPlace ? $version : null);

        $service = $this->createService(
            $repository,
            $serviceResolver,
            $this->createMock(SecurityServiceInterface::class),
            $publisher
        );

        $this->assertSame(
            $publishedInPlace ? self::VERSION_ID : self::VERSION_ID + 1,
            $service->publishVersion(self::VERSION_ID, $user)
        );
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

    private static function coreSupportsPublishingInPlace(): bool
    {
        return method_exists(AbstractElement::class, 'resolveExistingVersionParameter');
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

    private function createVersion(int $id = self::VERSION_ID, int $versionCount = 0, int $date = 0): Version
    {
        // Version's constructor pulls its services from the container
        /** @var Version $version */
        $version = (new ReflectionClass(Version::class))->newInstanceWithoutConstructor();
        $version->setId($id);
        $version->setVersionCount($versionCount);
        $version->setDate($date);

        return $version;
    }

    private function createCurrentElement(): Concrete
    {
        $currentElement = $this->createMock(Concrete::class);
        $currentElement->method('getVersionCount')->willReturn(self::ELEMENT_VERSION_COUNT);
        $currentElement->method('getModificationDate')->willReturn(self::ELEMENT_MODIFICATION_DATE);

        return $currentElement;
    }
}
