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
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDataService;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Model\Asset;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use function array_merge;

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
        $service = new ElementDataService($securityService, $this->makeEmpty(ServiceResolverInterface::class));

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
        $service = new ElementDataService($securityService, $this->makeEmpty(ServiceResolverInterface::class));

        $data = $service->getRelatedElementData($this->makeAsset(isAllowed: true, calls: $calls));

        $this->assertFalse($data->getHasViewAccess());
        $this->assertSame([], $calls);
    }

    /**
     * @throws Exception
     */
    public function testRelatedElementDataFromObjectSummary(): void
    {
        $data = $this->createServiceWithoutUser()->getRelatedElementDataFromSummary(
            $this->makeSummary(['className' => 'Product', 'subtype' => 'object', 'published' => false])
        );

        $this->assertSame(83, $data->getId());
        $this->assertSame('object', $data->getType());
        $this->assertSame('Product', $data->getSubtype());
        $this->assertSame('/path/to/element', $data->getFullPath());
        $this->assertFalse($data->getIsPublished());
    }

    /**
     * @throws Exception
     */
    public function testRelatedElementDataFromFolderSummaryHasNoPublishedState(): void
    {
        $data = $this->createServiceWithoutUser()->getRelatedElementDataFromSummary(
            $this->makeSummary(['className' => null, 'subtype' => 'folder'])
        );

        $this->assertSame('folder', $data->getSubtype());
        $this->assertNull($data->getIsPublished());
    }

    /**
     * @throws Exception
     */
    public function testRelatedElementDataFromAssetSummaryHasNoPublishedState(): void
    {
        $data = $this->createServiceWithoutUser()->getRelatedElementDataFromSummary(
            $this->makeSummary(['type' => 'asset', 'className' => null, 'subtype' => 'image', 'published' => true])
        );

        $this->assertSame('asset', $data->getType());
        $this->assertSame('image', $data->getSubtype());
        $this->assertNull($data->getIsPublished());
    }

    /**
     * @throws Exception
     */
    public function testRelatedElementDataFromDocumentSummaryKeepsPublishedState(): void
    {
        $data = $this->createServiceWithoutUser()->getRelatedElementDataFromSummary(
            $this->makeSummary(['type' => 'document', 'className' => null, 'subtype' => 'page', 'published' => false])
        );

        $this->assertSame('document', $data->getType());
        $this->assertSame('page', $data->getSubtype());
        $this->assertFalse($data->getIsPublished());
    }

    /**
     * @throws Exception
     */
    public function testSummaryOfUnknownClassIsResolvedThroughTheElement(): void
    {
        $asset = $this->makeAsset(isAllowed: true, calls: $calls);
        $resolver = $this->makeEmpty(ServiceResolverInterface::class, [
            'getElementById' => function (string $type, int $id) use ($asset) {
                $this->assertSame('object', $type);
                $this->assertSame(83, $id);

                return $asset;
            },
        ]);
        $securityService = $this->makeEmpty(SecurityServiceInterface::class, ['getCurrentUser' => new User()]);
        $service = new ElementDataService($securityService, $resolver);

        $data = $service->getRelatedElementDataFromSummary(
            $this->makeSummary(['className' => 'ClassThatDoesNotExistAnymore'])
        );

        $this->assertTrue($data->getHasViewAccess());
        $this->assertSame([[ElementPermissions::VIEW_PERMISSION, true]], $calls);
        $this->assertSame('/path/to/asset.jpg', $data->getFullPath());
    }

    /**
     * @throws Exception
     */
    public function testSummaryOfVanishedElementHasNoViewAccess(): void
    {
        $resolver = $this->makeEmpty(ServiceResolverInterface::class, ['getElementById' => null]);
        $securityService = $this->makeEmpty(SecurityServiceInterface::class, ['getCurrentUser' => new User()]);
        $service = new ElementDataService($securityService, $resolver);

        $data = $service->getRelatedElementDataFromSummary(
            $this->makeSummary(['className' => 'ClassThatDoesNotExistAnymore'])
        );

        $this->assertFalse($data->getHasViewAccess());
        $this->assertSame('ClassThatDoesNotExistAnymore', $data->getSubtype());
        $this->assertSame('/path/to/element', $data->getFullPath());
    }

    /**
     * @throws Exception
     */
    private function createServiceWithoutUser(): ElementDataService
    {
        $securityService = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => static function (): UserInterface {
                throw new UserNotFoundException();
            },
        ]);

        return new ElementDataService($securityService, $this->makeEmpty(ServiceResolverInterface::class));
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array{id: int, parentId: int|null, type: string, subtype: string, className: string|null, fullpath: string, filename: string, published: bool}
     */
    private function makeSummary(array $overrides = []): array
    {
        return array_merge([
            'id' => 83,
            'parentId' => 1,
            'type' => 'object',
            'subtype' => 'object',
            'className' => 'Product',
            'fullpath' => '/path/to/element',
            'filename' => 'element',
            'published' => true,
        ], $overrides);
    }

    /**
     * @throws Exception
     */
    private function createService(): ElementDataService
    {
        $securityService = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => new User(),
        ]);

        return new ElementDataService($securityService, $this->makeEmpty(ServiceResolverInterface::class));
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
