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
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDataService;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Model\Asset;
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
    public function testHasViewAccessTrueWhenViewIsAllowed(): void
    {
        $service = $this->createService();

        $data = $service->getRelatedElementData($this->makeAsset(isAllowed: true, calls: $calls));

        $this->assertTrue($data->getHasViewAccess());
        $this->assertSame([[ElementPermissions::VIEW_PERMISSION, true]], $calls);
        $this->assertSame(83, $data->getId());
        $this->assertSame('asset', $data->getType());
        $this->assertSame('image', $data->getSubtype());
        $this->assertSame('/path/to/asset.jpg', $data->getFullPath());
        $this->assertNull($data->getIsPublished());
    }

    /**
     * @throws Exception
     */
    public function testHasViewAccessFalseWhenViewIsDenied(): void
    {
        $service = $this->createService();

        $data = $service->getRelatedElementData($this->makeAsset(isAllowed: false, calls: $calls));

        $this->assertFalse($data->getHasViewAccess());
        $this->assertSame([[ElementPermissions::VIEW_PERMISSION, true]], $calls);
    }

    /**
     * @throws Exception
     */
    public function testHasViewAccessFalseWhenNoUserIsAuthenticated(): void
    {
        $securityService = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => static function (): UserInterface {
                throw new UserNotFoundException();
            },
        ]);
        $service = new ElementDataService($securityService);

        $data = $service->getRelatedElementData($this->makeAsset(isAllowed: true, calls: $calls));

        $this->assertFalse($data->getHasViewAccess());
        $this->assertSame([], $calls);
    }

    /**
     * @throws Exception
     */
    public function testHasViewAccessFalseWhenUserIsNotAPimcoreUser(): void
    {
        $securityService = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => $this->makeEmpty(UserInterface::class),
        ]);
        $service = new ElementDataService($securityService);

        $data = $service->getRelatedElementData($this->makeAsset(isAllowed: true, calls: $calls));

        $this->assertFalse($data->getHasViewAccess());
        $this->assertSame([], $calls);
    }

    /**
     * @throws Exception
     */
    private function createService(): ElementDataService
    {
        $securityService = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => new User(),
        ]);

        return new ElementDataService($securityService);
    }

    /**
     * @param array<int, array{string, bool}>|null $calls
     *
     * @throws Exception
     */
    private function makeAsset(bool $isAllowed, ?array &$calls = null): Asset
    {
        $calls = [];

        return $this->makeEmpty(Asset::class, [
            'getId' => 83,
            'getType' => 'image',
            'getRealFullPath' => '/path/to/asset.jpg',
            'isAllowed' => function (string $type, ?User $user = null) use (&$calls, $isAllowed): bool {
                $calls[] = [$type, $user instanceof User];

                return $isAllowed;
            },
        ]);
    }
}
