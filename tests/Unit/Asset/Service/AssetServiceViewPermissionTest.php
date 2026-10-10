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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Asset\Service;

use Codeception\Stub\Expected;
use Codeception\Stub\StubMarshaler;
use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\AssetFolder;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetService;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\AssetQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowDetailsServiceInterface;
use Pimcore\Model\Asset\Folder as CoreFolder;
use Pimcore\Model\User;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class AssetServiceViewPermissionTest extends Unit
{
    private const int ELEMENT_ID = 9;

    private User $user;

    protected function _before(): void
    {
        $this->user = new User();
    }

    public function testGetAssetRejectsElementWithoutViewPermission(): void
    {
        $service = $this->createService(false, Expected::never());

        $this->expectException(ForbiddenException::class);
        $service->getAsset(self::ELEMENT_ID);
    }

    public function testGetAssetForUserRejectsElementWithoutViewPermission(): void
    {
        $service = $this->createService(false, Expected::never());

        $this->expectException(ForbiddenException::class);
        $service->getAssetForUser(self::ELEMENT_ID, $this->user);
    }

    public function testGetAssetReportsElementWithoutListPermissionAsNotFound(): void
    {
        $service = $this->createService(false, Expected::never(), null, false);

        $this->expectException(NotFoundException::class);
        $service->getAsset(self::ELEMENT_ID);
    }

    public function testGetAssetForUserReturnsViewableElement(): void
    {
        $element = $this->createElement();
        $service = $this->createService(true, Expected::once(static fn (object $event) => $event), $element);

        $this->assertSame($element, $service->getAssetForUser(self::ELEMENT_ID, $this->user));
    }

    public function testGetAssetReturnsViewableElement(): void
    {
        $element = $this->createElement();
        $service = $this->createService(true, Expected::once(static fn (object $event) => $event), $element);

        $this->assertSame($element, $service->getAsset(self::ELEMENT_ID, false));
    }

    private function createElement(): AssetFolder
    {
        return (new ReflectionClass(AssetFolder::class))->newInstanceWithoutConstructor();
    }

    private function createService(bool $view, StubMarshaler $dispatch, ?AssetFolder $element = null, bool $list = true): AssetService
    {
        // Without view permission, the search index must not be queried at all.
        $lookup = $view ? Expected::once(function (int $id, ?User $user) use ($element) {
            $this->assertSame(self::ELEMENT_ID, $id);
            $this->assertSame($this->user, $user);

            return $element;
        }) : Expected::never();
        $searchService = $this->makeEmpty(AssetSearchServiceInterface::class, ['getAssetById' => $lookup]);
        $coreElement = $this->makeEmpty(CoreFolder::class, [
            'isAllowed' => function (string $permission, User $user) use ($view, $list): bool {
                $this->assertSame($this->user, $user);

                return $permission === ElementPermissions::VIEW_PERMISSION ? $view : $list;
            },
        ]);

        return new AssetService(
            $this->makeEmpty(AssetQueryProviderInterface::class),
            $searchService,
            $this->makeEmpty(AssetServiceResolverInterface::class),
            $this->makeEmpty(EventDispatcherInterface::class, ['dispatch' => $dispatch]),
            $this->makeEmpty(FilterServiceProviderInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class, ['getCurrentUser' => $this->user]),
            $this->makeEmpty(ServiceResolverInterface::class, ['getElementById' => $coreElement]),
            $this->makeEmpty(WorkflowDetailsServiceInterface::class, ['hasElementWorkflowsById' => Expected::never()]),
        );
    }
}
