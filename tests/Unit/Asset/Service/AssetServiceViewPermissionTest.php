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
use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\AssetFolder;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetService;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\AssetQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowDetailsServiceInterface;
use Pimcore\Model\Asset\Folder;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\User;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class AssetServiceViewPermissionTest extends Unit
{
    private const int ASSET_ID = 9;

    public function testGetAssetRequiresViewPermission(): void
    {
        $folder = $this->makeEmpty(Folder::class);

        $service = $this->createService($this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => new User(),
            'hasElementPermission' => Expected::once(
                function (ElementInterface $element, User $user, string $permission) use ($folder) {
                    $this->assertSame($folder, $element);
                    $this->assertSame(ElementPermissions::VIEW_PERMISSION, $permission);

                    throw new ForbiddenException();
                }
            ),
        ]), $folder);

        $this->expectException(ForbiddenException::class);
        $service->getAsset(self::ASSET_ID);
    }

    public function testGetAssetForUserRequiresViewPermission(): void
    {
        $service = $this->createService($this->makeEmpty(SecurityServiceInterface::class, [
            'hasElementPermission' => static function () {
                throw new ForbiddenException();
            },
        ]), $this->makeEmpty(Folder::class));

        $this->expectException(ForbiddenException::class);
        $service->getAssetForUser(self::ASSET_ID, new User());
    }

    private function createService(SecurityServiceInterface $securityService, ElementInterface $element): AssetService
    {
        return new AssetService(
            $this->makeEmpty(AssetQueryProviderInterface::class),
            $this->makeEmpty(AssetSearchServiceInterface::class, [
                'getAssetById' => (new ReflectionClass(AssetFolder::class))->newInstanceWithoutConstructor(),
            ]),
            $this->makeEmpty(AssetServiceResolverInterface::class),
            $this->makeEmpty(EventDispatcherInterface::class, ['dispatch' => Expected::never()]),
            $this->makeEmpty(FilterServiceProviderInterface::class),
            $securityService,
            $this->makeEmpty(ServiceResolverInterface::class, ['getElementById' => $element]),
            $this->makeEmpty(WorkflowDetailsServiceInterface::class, ['hasElementWorkflowsById' => Expected::never()]),
        );
    }
}
