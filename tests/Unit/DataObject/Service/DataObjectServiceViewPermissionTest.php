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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataObject\Service;

use Codeception\Stub\Expected;
use Codeception\Stub\StubMarshaler;
use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\DataObjectQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\DataObjectSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Type\DataObjectFolder;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataObjectService;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementSaveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Model\DataObject\Folder as CoreFolder;
use Pimcore\Model\FactoryInterface;
use Pimcore\Model\User;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class DataObjectServiceViewPermissionTest extends Unit
{
    private const int ELEMENT_ID = 9;

    private User $user;

    protected function _before(): void
    {
        $this->user = new User();
    }

    public function testGetDataObjectRejectsElementWithoutViewPermission(): void
    {
        $service = $this->createService(false, Expected::never());

        $this->expectException(ForbiddenException::class);
        $service->getDataObject(self::ELEMENT_ID);
    }

    public function testGetDataObjectForUserRejectsElementWithoutViewPermission(): void
    {
        $service = $this->createService(false, Expected::never());

        $this->expectException(ForbiddenException::class);
        $service->getDataObjectForUser(self::ELEMENT_ID, $this->user);
    }

    public function testGetDataObjectForUserReturnsViewableElement(): void
    {
        $element = $this->createElement();
        $service = $this->createService(true, Expected::once(static fn (object $event) => $event), $element);

        $this->assertSame($element, $service->getDataObjectForUser(self::ELEMENT_ID, $this->user));
    }

    public function testGetDataObjectReturnsViewableElement(): void
    {
        $element = $this->createElement();
        $service = $this->createService(true, Expected::once(static fn (object $event) => $event), $element);

        $this->assertSame($element, $service->getDataObject(self::ELEMENT_ID, false));
    }

    private function createElement(): DataObjectFolder
    {
        return (new ReflectionClass(DataObjectFolder::class))->newInstanceWithoutConstructor();
    }

    private function createService(bool $view, StubMarshaler $dispatch, ?DataObjectFolder $element = null): DataObjectService
    {
        // Without view permission, the search index must not be queried at all.
        $lookup = $view ? Expected::once(function (int $id, ?User $user) use ($element) {
            $this->assertSame(self::ELEMENT_ID, $id);
            $this->assertSame($this->user, $user);

            return $element;
        }) : Expected::never();
        $searchService = $this->makeEmpty(DataObjectSearchServiceInterface::class, ['getDataObjectById' => $lookup]);
        $coreElement = $this->makeEmpty(CoreFolder::class, [
            'isAllowed' => function (string $permission, User $user) use ($view): bool {
                $this->assertSame(ElementPermissions::VIEW_PERMISSION, $permission);
                $this->assertSame($this->user, $user);

                return $view;
            },
        ]);

        return new DataObjectService(
            $this->makeEmpty(ClassDefinitionResolverInterface::class),
            $this->makeEmpty(DataServiceInterface::class, ['setObjectDetailData' => Expected::never()]),
            $this->makeEmpty(DataObjectQueryProviderInterface::class),
            $searchService,
            $this->makeEmpty(DataObjectServiceResolverInterface::class),
            $this->makeEmpty(FactoryInterface::class),
            $this->makeEmpty(FilterServiceProviderInterface::class),
            $this->makeEmpty(EventDispatcherInterface::class, ['dispatch' => $dispatch]),
            $this->makeEmpty(SecurityServiceInterface::class, ['getCurrentUser' => $this->user]),
            $this->makeEmpty(ServiceResolverInterface::class, ['getElementById' => $coreElement]),
            $this->makeEmpty(ElementSaveServiceInterface::class),
        );
    }
}
