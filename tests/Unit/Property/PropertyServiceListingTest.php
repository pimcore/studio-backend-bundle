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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Property;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Property\Hydrator\PropertyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\MappedParameter\PropertiesParameters;
use Pimcore\Bundle\StudioBackendBundle\Property\Repository\PropertyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Property\Service\PropertyService;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\Property\Predefined\Listing;
use Pimcore\Model\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class PropertyServiceListingTest extends Unit
{
    public function testUnfilteredListingDeniedWithoutPermission(): void
    {
        $service = $this->buildService(['objects'], 0);
        $this->expectException(ForbiddenException::class);
        $service->getPredefinedProperties(new PropertiesParameters());
    }

    public function testUnfilteredListingAllowedWithPermission(): void
    {
        $service = $this->buildService(['predefined_properties'], 1);
        $this->assertSame([], $service->getPredefinedProperties(new PropertiesParameters()));
    }

    public function testUnfilteredListingAllowedForAdmin(): void
    {
        $service = $this->buildService([], 1, true);
        $this->assertSame([], $service->getPredefinedProperties(new PropertiesParameters()));
    }

    public function testObjectFilteredListingAllowedWithObjectsPermission(): void
    {
        $service = $this->buildService(['objects'], 1);
        $this->assertSame([], $service->getPredefinedProperties(new PropertiesParameters(elementType: 'object')));
    }

    public function testDataObjectFilteredListingAllowedWithObjectsPermission(): void
    {
        $service = $this->buildService(['objects'], 1);
        $this->assertSame(
            [],
            $service->getPredefinedProperties(new PropertiesParameters(elementType: 'data-object'))
        );
    }

    public function testAssetFilteredListingAllowedWithAssetsPermission(): void
    {
        $service = $this->buildService(['assets'], 1);
        $this->assertSame([], $service->getPredefinedProperties(new PropertiesParameters(elementType: 'asset')));
    }

    public function testDocumentFilteredListingAllowedWithDocumentsPermission(): void
    {
        $service = $this->buildService(['documents'], 1);
        $this->assertSame([], $service->getPredefinedProperties(new PropertiesParameters(elementType: 'document')));
    }

    public function testAssetFilteredListingAllowedWithOnlyAssetsPermissionNotDocuments(): void
    {
        $service = $this->buildService(['assets'], 0);
        $this->expectException(ForbiddenException::class);
        $service->getPredefinedProperties(new PropertiesParameters(elementType: 'document'));
    }

    public function testAssetFilteredListingDeniedWithOnlyObjectsPermission(): void
    {
        $service = $this->buildService(['objects'], 0);
        $this->expectException(ForbiddenException::class);
        $service->getPredefinedProperties(new PropertiesParameters(elementType: 'asset'));
    }

    public function testDocumentFilteredListingDeniedWithOnlyObjectsPermission(): void
    {
        $service = $this->buildService(['objects'], 0);
        $this->expectException(ForbiddenException::class);
        $service->getPredefinedProperties(new PropertiesParameters(elementType: 'document'));
    }

    public function testUnknownElementTypeListingDeniedWithoutPermission(): void
    {
        $service = $this->buildService(['objects', 'assets', 'documents'], 0);
        $this->expectException(ForbiddenException::class);
        $service->getPredefinedProperties(new PropertiesParameters(elementType: 'unknown'));
    }

    /**
     * @param array<int, string> $permissions
     */
    private function buildService(array $permissions, int $expectedListCalls, bool $admin = false): PropertyService
    {
        $user = new User();
        $user->setName('testuser');
        $user->setAdmin($admin);
        $user->setPermissions($permissions);

        $security = $this->createMock(SecurityServiceInterface::class);
        $security->method('getCurrentUser')->willReturn($user);

        $listing = new class() extends Listing {
            public function load(): array
            {
                return [];
            }
        };

        $repository = $this->createMock(PropertyRepositoryInterface::class);
        $repository->expects($this->exactly($expectedListCalls))
            ->method('listProperties')
            ->willReturn($listing);

        return new PropertyService(
            $repository,
            $this->createMock(PropertyHydratorInterface::class),
            $security,
            $this->createMock(ServiceResolverInterface::class),
            $this->createMock(EventDispatcherInterface::class)
        );
    }
}
