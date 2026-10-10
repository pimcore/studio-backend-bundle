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
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\FactoryInterface;
use Pimcore\Model\User;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class DataObjectServiceViewPermissionTest extends Unit
{
    private const int OBJECT_ID = 9;

    public function testGetDataObjectRequiresViewPermission(): void
    {
        $user = new User();
        $folder = $this->makeEmpty(Folder::class);

        $service = $this->createService(
            $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $user,
                'hasElementPermission' => Expected::once(
                    function (ElementInterface $element, User $permissionUser, string $permission) use ($folder) {
                        $this->assertSame($folder, $element);
                        $this->assertSame(ElementPermissions::VIEW_PERMISSION, $permission);

                        throw new ForbiddenException();
                    }
                ),
            ]),
            $folder
        );

        $this->expectException(ForbiddenException::class);
        $service->getDataObject(self::OBJECT_ID);
    }

    public function testGetDataObjectForUserRequiresViewPermission(): void
    {
        $user = new User();

        $service = $this->createService(
            $this->makeEmpty(SecurityServiceInterface::class, [
                'hasElementPermission' => Expected::once(
                    function (ElementInterface $element, User $permissionUser) use ($user) {
                        $this->assertSame($user, $permissionUser);

                        throw new ForbiddenException();
                    }
                ),
            ]),
            $this->makeEmpty(Folder::class)
        );

        $this->expectException(ForbiddenException::class);
        $service->getDataObjectForUser(self::OBJECT_ID, $user);
    }

    public function testGetDataObjectForUserReturnsViewableObject(): void
    {
        $user = new User();
        $dataObject = (new ReflectionClass(DataObjectFolder::class))->newInstanceWithoutConstructor();

        $service = $this->createService(
            $this->makeEmpty(SecurityServiceInterface::class, ['hasElementPermission' => Expected::once()]),
            $this->makeEmpty(Folder::class),
            $dataObject
        );

        $this->assertSame($dataObject, $service->getDataObjectForUser(self::OBJECT_ID, $user));
    }

    private function createService(
        SecurityServiceInterface $securityService,
        ElementInterface $element,
        ?DataObjectFolder $dataObject = null
    ): DataObjectService {
        $dataObject ??= (new ReflectionClass(DataObjectFolder::class))->newInstanceWithoutConstructor();

        return new DataObjectService(
            $this->makeEmpty(ClassDefinitionResolverInterface::class),
            $this->makeEmpty(DataServiceInterface::class, ['setObjectDetailData' => Expected::never()]),
            $this->makeEmpty(DataObjectQueryProviderInterface::class),
            $this->makeEmpty(DataObjectSearchServiceInterface::class, [
                'getDataObjectById' => $dataObject,
            ]),
            $this->makeEmpty(DataObjectServiceResolverInterface::class),
            $this->makeEmpty(FactoryInterface::class),
            $this->makeEmpty(FilterServiceProviderInterface::class),
            $this->makeEmpty(EventDispatcherInterface::class),
            $securityService,
            $this->makeEmpty(ServiceResolverInterface::class, ['getElementById' => $element]),
            $this->makeEmpty(ElementSaveServiceInterface::class),
        );
    }
}
