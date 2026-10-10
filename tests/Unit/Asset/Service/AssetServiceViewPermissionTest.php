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

use Closure;
use Codeception\Stub\Expected;
use Codeception\Stub\StubMarshaler;
use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Asset;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\AssetPermissions;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\AssetFolder;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetService;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\AssetQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowDetailsServiceInterface;
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
        $service = $this->createService($this->createElement(false), Expected::never());

        $this->expectException(ForbiddenException::class);
        $service->getAsset(self::ELEMENT_ID);
    }

    public function testGetAssetForUserRejectsElementWithoutViewPermission(): void
    {
        $service = $this->createService($this->createElement(false), Expected::never());

        $this->expectException(ForbiddenException::class);
        $service->getAssetForUser(self::ELEMENT_ID, $this->user);
    }

    public function testGetAssetForUserReturnsViewableElement(): void
    {
        $element = $this->createElement(true);
        $service = $this->createService($element, Expected::once(static fn (object $event) => $event));

        $this->assertSame($element, $service->getAssetForUser(self::ELEMENT_ID, $this->user));
    }

    public function testGetAssetReturnsViewableElement(): void
    {
        $element = $this->createElement(true);
        $service = $this->createService($element, Expected::once(static fn (object $event) => $event));

        $this->assertSame($element, $service->getAsset(self::ELEMENT_ID, false));
    }

    private function createElement(bool $view): AssetFolder
    {
        $element = (new ReflectionClass(AssetFolder::class))->newInstanceWithoutConstructor();
        // The permissions property is declared private in the parent schema class.
        Closure::bind(
            function ($permissions): void {
                $this->permissions = $permissions;
            },
            $element,
            Asset::class
        )(new AssetPermissions(view: $view));

        return $element;
    }

    private function createService(AssetFolder $element, StubMarshaler $dispatch): AssetService
    {
        $searchService = $this->makeEmpty(AssetSearchServiceInterface::class, [
            'getAssetById' => Expected::once(function (int $id, ?User $user) use ($element) {
                $this->assertSame(self::ELEMENT_ID, $id);
                $this->assertSame($this->user, $user);

                return $element;
            }),
        ]);

        return new AssetService(
            $this->makeEmpty(AssetQueryProviderInterface::class),
            $searchService,
            $this->makeEmpty(AssetServiceResolverInterface::class),
            $this->makeEmpty(EventDispatcherInterface::class, ['dispatch' => $dispatch]),
            $this->makeEmpty(FilterServiceProviderInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class, ['getCurrentUser' => $this->user]),
            $this->makeEmpty(ServiceResolverInterface::class),
            $this->makeEmpty(WorkflowDetailsServiceInterface::class, ['hasElementWorkflowsById' => Expected::never()]),
        );
    }
}
