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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\RecycleBin\Service;

use Codeception\Test\Unit;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Document\DocumentServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\RecycleBin\Service\FallbackFolderService;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseErrorKeys;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Folder as AssetFolder;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Folder as DataObjectFolder;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Folder as DocumentFolder;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class FallbackFolderServiceTest extends Unit
{
    /**
     * @throws Exception
     * @dataProvider parentWithoutLookupProvider
     */
    #[DataProvider('parentWithoutLookupProvider')]
    public function testReportsAMissingParentIdWithoutALookup(?int $parentId): void
    {
        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver->expects($this->never())->method('getElementById');

        $element = $this->createMock(Asset::class);
        $element->method('getParentId')->willReturn($parentId);

        $this->assertTrue($this->createService(serviceResolver: $serviceResolver)->hasMissingParent($element));
    }

    public static function parentWithoutLookupProvider(): array
    {
        return [
            'parent id null' => [null],
            'parent id 0' => [0],
        ];
    }

    /**
     * @throws Exception
     * @dataProvider parentLookupProvider
     */
    #[DataProvider('parentLookupProvider')]
    public function testLooksUpTheParentWithTheCoreElementType(
        string $elementClass,
        string $coreType,
        bool $parentExists,
        bool $expected
    ): void {
        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver->expects($this->once())
            ->method('getElementById')
            ->with($coreType, 7)
            ->willReturn($parentExists ? $this->createMock($elementClass) : null);

        $element = $this->createMock($elementClass);
        $element->method('getParentId')->willReturn(7);

        $this->assertSame(
            $expected,
            $this->createService(serviceResolver: $serviceResolver)->hasMissingParent($element)
        );
    }

    public static function parentLookupProvider(): array
    {
        return [
            'asset parent deleted' => [Asset::class, 'asset', false, true],
            'document parent deleted' => [Document::class, 'document', false, true],
            'data object parent deleted' => [Concrete::class, 'object', false, true],
            'asset parent exists' => [Asset::class, 'asset', true, false],
            'data object parent exists' => [Concrete::class, 'object', true, false],
        ];
    }

    /**
     * @throws Exception
     * @dataProvider elementTypeProvider
     */
    #[DataProvider('elementTypeProvider')]
    public function testMovesTheElementIntoTheFallbackFolderOfItsType(
        string $elementClass,
        string $folderClass,
        string $resolver,
        string $coreType
    ): void {
        $user = $this->makeEmpty(UserInterface::class);
        $folder = $this->createMock($folderClass);
        $element = $this->createMock($elementClass);
        $element->method('getKey')->willReturn('my-key');

        $resolvers = $this->createResolvers($resolver, $element, ['my-key']);

        $securityService = $this->createMock(SecurityServiceInterface::class);
        $securityService->expects($this->once())
            ->method('hasElementPermission')
            ->with($folder, $user, 'publish');

        $element->expects($this->once())->method('setParent')->with($folder);
        $element->expects($this->never())->method('setKey');

        $this->createService(
            ...$resolvers,
            securityService: $securityService,
            serviceResolver: $this->createServiceResolver($coreType, $folder)
        )->moveToFallbackFolder($element, $user);
    }

    /**
     * @throws Exception
     * @dataProvider elementTypeProvider
     */
    #[DataProvider('elementTypeProvider')]
    public function testCreatesTheFallbackFolderAfterThePublishCheckOnTheRoot(
        string $elementClass,
        string $folderClass,
        string $resolver,
        string $coreType
    ): void {
        $user = $this->makeEmpty(UserInterface::class);
        $root = $this->createMock($folderClass);
        $folder = $this->createMock($folderClass);
        $element = $this->createMock($elementClass);
        $element->method('getKey')->willReturn('my-key');

        $resolvers = $this->createResolvers($resolver, $element, ['my-key'], $folder);

        $securityService = $this->createMock(SecurityServiceInterface::class);
        $securityService->expects($this->once())
            ->method('hasElementPermission')
            ->with($root, $user, 'publish');

        $element->expects($this->once())->method('setParent')->with($folder);

        $this->createService(
            ...$resolvers,
            securityService: $securityService,
            serviceResolver: $this->createServiceResolver($coreType, null, $root)
        )->moveToFallbackFolder($element, $user);
    }

    /**
     * @throws Exception
     * @dataProvider takenKeyProvider
     */
    #[DataProvider('takenKeyProvider')]
    public function testAddsTheRestoreSuffixWhenTheKeyIsTakenInTheFallbackFolder(
        string $elementClass,
        string $folderClass,
        string $resolver,
        string $coreType,
        string $key,
        array $uniqueKeys,
        array $expectedKeys
    ): void {
        $folder = $this->createMock($folderClass);
        $element = $this->createMock($elementClass);
        $element->method('getKey')->willReturn($key);
        $element->expects($this->exactly(2))->method('setKey')->willReturnCallback(
            function (string $key) use (&$expectedKeys, $element): ElementInterface {
                $this->assertSame(array_shift($expectedKeys), $key);

                return $element;
            }
        );

        $this->createService(
            ...$this->createResolvers($resolver, $element, $uniqueKeys),
            serviceResolver: $this->createServiceResolver($coreType, $folder)
        )->moveToFallbackFolder($element, $this->makeEmpty(UserInterface::class));

        $this->assertSame([], $expectedKeys);
    }

    public static function takenKeyProvider(): array
    {
        $asset = [Asset::class, AssetFolder::class, 'assetServiceResolver', 'asset'];

        return [
            'asset' => [...$asset, 'image.jpg', ['image_1.jpg', 'image_restore.jpg'], [
                'image_restore.jpg',
                'image_restore.jpg',
            ]],
            'asset with the restore suffix taken too' => [...$asset, 'image.jpg', [
                'image_1.jpg',
                'image_restore_1.jpg',
            ], ['image_restore.jpg', 'image_restore_1.jpg']],
            'asset without extension' => [...$asset, 'README', ['README_1', 'README_restore'], [
                'README_restore',
                'README_restore',
            ]],
            'asset folder' => [AssetFolder::class, AssetFolder::class, 'assetServiceResolver', 'asset', 'photos.2024', [
                'photos.2024_1',
                'photos.2024_restore',
            ], ['photos.2024_restore', 'photos.2024_restore']],
            'document' => [Document::class, DocumentFolder::class, 'documentServiceResolver', 'document', 'about.us', [
                'about.us_1',
                'about.us_restore',
            ], ['about.us_restore', 'about.us_restore']],
            'data object' => [Concrete::class, DataObjectFolder::class, 'dataObjectServiceResolver', 'object', 'car.a', [
                'car.a_1',
                'car.a_restore',
            ], ['car.a_restore', 'car.a_restore']],
        ];
    }

    public static function elementTypeProvider(): array
    {
        return [
            'asset' => [Asset::class, AssetFolder::class, 'assetServiceResolver', 'asset'],
            'document' => [Document::class, DocumentFolder::class, 'documentServiceResolver', 'document'],
            'data object' => [Concrete::class, DataObjectFolder::class, 'dataObjectServiceResolver', 'object'],
        ];
    }

    /**
     * @throws Exception
     */
    public function testFailsWithTheMissingParentKeyWithoutPublishPermissionOnTheFallbackFolder(): void
    {
        $folder = $this->createMock(AssetFolder::class);
        $element = $this->createMock(Asset::class);
        $element->expects($this->never())->method('setParent');
        $element->expects($this->never())->method('setKey');

        $securityService = $this->createMock(SecurityServiceInterface::class);
        $securityService->method('hasElementPermission')->willThrowException(new ForbiddenException());

        $service = $this->createService(
            ...$this->createResolvers('assetServiceResolver', $element, ['my-key']),
            securityService: $securityService,
            serviceResolver: $this->createServiceResolver('asset', $folder)
        );

        $this->assertMissingParentException(
            fn () => $service->moveToFallbackFolder($element, $this->makeEmpty(UserInterface::class))
        );
    }

    /**
     * @throws Exception
     */
    public function testDoesNotCreateTheFallbackFolderWithoutPublishPermissionOnTheRoot(): void
    {
        $root = $this->createMock(AssetFolder::class);
        $element = $this->createMock(Asset::class);
        $element->expects($this->never())->method('setParent');

        $securityService = $this->createMock(SecurityServiceInterface::class);
        $securityService->expects($this->once())
            ->method('hasElementPermission')
            ->with($root)
            ->willThrowException(new ForbiddenException());

        $assetServiceResolver = $this->createMock(AssetServiceResolverInterface::class);
        $assetServiceResolver->expects($this->never())->method('createFolderByPath');

        $service = $this->createService(
            assetServiceResolver: $assetServiceResolver,
            securityService: $securityService,
            serviceResolver: $this->createServiceResolver('asset', null, $root)
        );

        $this->assertMissingParentException(
            fn () => $service->moveToFallbackFolder($element, $this->makeEmpty(UserInterface::class))
        );
    }

    /**
     * @throws Exception
     */
    public function testFailsWithTheMissingParentKeyWhenTheRootIsMissing(): void
    {
        $element = $this->createMock(Asset::class);
        $element->expects($this->never())->method('setParent');

        $assetServiceResolver = $this->createMock(AssetServiceResolverInterface::class);
        $assetServiceResolver->expects($this->never())->method('createFolderByPath');

        $service = $this->createService(
            assetServiceResolver: $assetServiceResolver,
            serviceResolver: $this->createServiceResolver('asset', null, null)
        );

        $this->assertMissingParentException(
            fn () => $service->moveToFallbackFolder($element, $this->makeEmpty(UserInterface::class))
        );
    }

    /**
     * @throws Exception
     * @dataProvider folderCreationFailureProvider
     */
    #[DataProvider('folderCreationFailureProvider')]
    public function testFailsWithTheMissingParentKeyWhenTheFallbackFolderCannotBeCreated(bool $throws): void
    {
        $assetServiceResolver = $this->createMock(AssetServiceResolverInterface::class);
        $creation = $assetServiceResolver->expects($this->once())->method('createFolderByPath')->with('/__restored');
        $throws ? $creation->willThrowException(new Exception('database error')) : $creation->willReturn(null);

        $element = $this->createMock(Asset::class);
        $element->expects($this->never())->method('setParent');

        $service = $this->createService(
            assetServiceResolver: $assetServiceResolver,
            serviceResolver: $this->createServiceResolver('asset', null, $this->createMock(AssetFolder::class))
        );

        $this->assertMissingParentException(
            fn () => $service->moveToFallbackFolder($element, $this->makeEmpty(UserInterface::class))
        );
    }

    public static function folderCreationFailureProvider(): array
    {
        return [
            'folder creation throws' => [true],
            'folder creation returns null' => [false],
        ];
    }

    /**
     * @param string[] $uniqueKeys what the type's resolver answers on consecutive getUniqueKey() calls
     *
     * @return array<string, object>
     */
    private function createResolvers(
        string $resolver,
        ElementInterface $element,
        array $uniqueKeys,
        ?ElementInterface $createdFolder = null
    ): array {
        $resolvers = [
            'assetServiceResolver' => $this->createMock(AssetServiceResolverInterface::class),
            'documentServiceResolver' => $this->createMock(DocumentServiceResolverInterface::class),
            'dataObjectServiceResolver' => $this->createMock(DataObjectServiceResolverInterface::class),
        ];

        foreach ($resolvers as $name => $mock) {
            if ($name !== $resolver) {
                $mock->expects($this->never())->method('createFolderByPath');

                continue;
            }

            $creation = $mock->expects($createdFolder === null ? $this->never() : $this->once())
                ->method('createFolderByPath');
            if ($createdFolder !== null) {
                $creation->with('/__restored')->willReturn($createdFolder);
            }
            $mock->expects($this->atMost(2))
                ->method('getUniqueKey')
                ->with($element)
                ->willReturnOnConsecutiveCalls(...$uniqueKeys);
        }

        return $resolvers;
    }

    /**
     * @param ElementInterface|null $folder the existing fallback folder
     * @param ElementInterface|null $root the root element, looked up only while the folder does not exist
     */
    private function createServiceResolver(
        string $coreType,
        ?ElementInterface $folder,
        ?ElementInterface $root = null
    ): ServiceResolverInterface {
        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver->expects($this->once())
            ->method('getElementByPath')
            ->with($coreType, '/__restored')
            ->willReturn($folder);
        $serviceResolver->expects($folder === null ? $this->once() : $this->never())
            ->method('getElementById')
            ->with($coreType, 1)
            ->willReturn($root);

        return $serviceResolver;
    }

    private function assertMissingParentException(callable $callable): void
    {
        try {
            $callable();
            $this->fail('EnvironmentException expected');
        } catch (EnvironmentException $exception) {
            $this->assertSame(
                HttpResponseErrorKeys::RECYCLE_BIN_RESTORE_MISSING_PARENT->value,
                $exception->getErrorKey()
            );
        }
    }

    private function createService(
        ?AssetServiceResolverInterface $assetServiceResolver = null,
        ?DataObjectServiceResolverInterface $dataObjectServiceResolver = null,
        ?DocumentServiceResolverInterface $documentServiceResolver = null,
        ?SecurityServiceInterface $securityService = null,
        ?ServiceResolverInterface $serviceResolver = null,
    ): FallbackFolderService {
        return new FallbackFolderService(
            $assetServiceResolver ?? $this->createMock(AssetServiceResolverInterface::class),
            $dataObjectServiceResolver ?? $this->createMock(DataObjectServiceResolverInterface::class),
            $documentServiceResolver ?? $this->createMock(DocumentServiceResolverInterface::class),
            $securityService ?? $this->createMock(SecurityServiceInterface::class),
            $serviceResolver ?? $this->createMock(ServiceResolverInterface::class),
        );
    }
}
