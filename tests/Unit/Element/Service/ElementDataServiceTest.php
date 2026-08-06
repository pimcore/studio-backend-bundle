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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Element\Service;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Permission\ElementPermissionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDataService;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class ElementDataServiceTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testHasAccessTrueWhenViewIsAllowed(): void
    {
        $service = $this->createService(isAllowed: true, calls: $calls);

        $data = $service->getRelatedElementData($this->makeAsset());

        $this->assertTrue($data->getHasAccess());
        $this->assertSame([[ElementPermissions::VIEW_PERMISSION, 83]], $calls);
        $this->assertSame(83, $data->getId());
        $this->assertSame('asset', $data->getType());
        $this->assertSame('image', $data->getSubtype());
        $this->assertSame('/path/to/asset.jpg', $data->getFullPath());
        $this->assertNull($data->getIsPublished());
    }

    /**
     * @throws Exception
     */
    public function testHasAccessFalseWhenViewIsDenied(): void
    {
        $service = $this->createService(isAllowed: false, calls: $calls);

        $data = $service->getRelatedElementData($this->makeAsset());

        $this->assertFalse($data->getHasAccess());
        $this->assertSame([[ElementPermissions::VIEW_PERMISSION, 83]], $calls);
    }

    /**
     * @throws Exception
     */
    public function testHasAccessFalseWhenNoUserIsAuthenticated(): void
    {
        $securityService = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => static function (): UserInterface {
                throw new UserNotFoundException();
            },
        ]);
        $service = new ElementDataService(
            $securityService,
            $this->makeEmpty(ElementPermissionServiceInterface::class)
        );

        $data = $service->getRelatedElementData($this->makeAsset());

        $this->assertFalse($data->getHasAccess());
    }

    /**
     * @throws Exception
     */
    public function testHasAccessFalseWhenUserIsNotAPimcoreUser(): void
    {
        $securityService = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => $this->makeEmpty(UserInterface::class),
        ]);
        $service = new ElementDataService(
            $securityService,
            $this->makeEmpty(ElementPermissionServiceInterface::class)
        );

        $data = $service->getRelatedElementData($this->makeAsset());

        $this->assertFalse($data->getHasAccess());
    }

    /**
     * @param array<int, array{string, int}>|null $calls
     *
     * @throws Exception
     */
    private function createService(bool $isAllowed, ?array &$calls = null): ElementDataService
    {
        $calls = [];

        $securityService = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => new User(),
        ]);

        $elementPermissionService = $this->makeEmpty(ElementPermissionServiceInterface::class, [
            'isAllowed' => function (
                string $permission,
                ElementInterface $element,
                User $user
            ) use (&$calls, $isAllowed): bool {
                $calls[] = [$permission, $element->getId()];

                return $isAllowed;
            },
        ]);

        return new ElementDataService($securityService, $elementPermissionService);
    }

    /**
     * @throws Exception
     */
    private function makeAsset(): Asset
    {
        return $this->makeEmpty(Asset::class, [
            'getId' => 83,
            'getType' => 'image',
            'getRealFullPath' => '/path/to/asset.jpg',
        ]);
    }
}
