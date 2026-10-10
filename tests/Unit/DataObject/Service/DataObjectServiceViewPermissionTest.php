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

use Closure;
use Codeception\Stub\Expected;
use Codeception\Stub\StubMarshaler;
use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\DataObjectQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\DataObjectSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObject;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObjectPermissions;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Type\DataObjectFolder;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataObjectService;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementSaveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
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

    public function testGetDataObjectRejectsElementWithoutViewPermission(): void
    {
        $service = $this->createService($this->createElement(false), Expected::never());

        $this->expectException(ForbiddenException::class);
        $service->getDataObject(self::ELEMENT_ID);
    }

    public function testGetDataObjectForUserRejectsElementWithoutViewPermission(): void
    {
        $service = $this->createService($this->createElement(false), Expected::never());

        $this->expectException(ForbiddenException::class);
        $service->getDataObjectForUser(self::ELEMENT_ID, new User());
    }

    public function testGetDataObjectForUserReturnsViewableElement(): void
    {
        $element = $this->createElement(true);
        $service = $this->createService($element, Expected::once(static fn (object $event) => $event));

        $this->assertSame($element, $service->getDataObjectForUser(self::ELEMENT_ID, new User()));
    }

    public function testGetDataObjectReturnsViewableElement(): void
    {
        $element = $this->createElement(true);
        $service = $this->createService($element, Expected::once(static fn (object $event) => $event));

        $this->assertSame($element, $service->getDataObject(self::ELEMENT_ID, false));
    }

    private function createElement(bool $view): DataObjectFolder
    {
        $element = (new ReflectionClass(DataObjectFolder::class))->newInstanceWithoutConstructor();
        // The permissions property is declared private in the parent schema class.
        Closure::bind(
            function ($permissions): void {
                $this->permissions = $permissions;
            },
            $element,
            DataObject::class
        )(new DataObjectPermissions(view: $view));

        return $element;
    }

    private function createService(DataObjectFolder $element, StubMarshaler $dispatch): DataObjectService
    {
        $searchService = $this->makeEmpty(DataObjectSearchServiceInterface::class, [
            'getDataObjectById' => Expected::once(function (int $id) use ($element) {
                $this->assertSame(self::ELEMENT_ID, $id);

                return $element;
            }),
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
            $this->makeEmpty(SecurityServiceInterface::class, ['getCurrentUser' => new User()]),
            $this->makeEmpty(ServiceResolverInterface::class),
            $this->makeEmpty(ElementSaveServiceInterface::class),
        );
    }
}
