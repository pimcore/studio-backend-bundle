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
        string $resolver
    ): void {
        $user = $this->makeEmpty(UserInterface::class);
        $folder = $this->createMock($folderClass);
        $element = $this->createMock($elementClass);
        $element->method('getKey')->willReturn('my-key');

        $resolvers = $this->createResolvers($resolver, $element, $folder, ['my-key']);

        $securityService = $this->createMock(SecurityServiceInterface::class);
        $securityService->expects($this->once())
            ->method('hasElementPermission')
            ->with($folder, $user, 'create');

        $element->expects($this->once())->method('setParent')->with($folder);
        $element->expects($this->never())->method('setKey');

        $this->createService(...$resolvers, securityService: $securityService)
            ->moveToFallbackFolder($element, $user);
    }

    /**
     * @throws Exception
     * @dataProvider takenKeyProvider
     */
    #[DataProvider('takenKeyProvider')]
    public function testAppendsTheRestoreSuffixWhenTheKeyIsTakenInTheFallbackFolder(
        array $uniqueKeys,
        array $expectedKeys
    ): void {
        $folder = $this->createMock(AssetFolder::class);
        $element = $this->createMock(Asset::class);
        $element->method('getKey')->willReturn('image.jpg');
        $element->expects($this->exactly(2))->method('setKey')->willReturnCallback(
            function (string $key) use (&$expectedKeys, $element): Asset {
                $this->assertSame(array_shift($expectedKeys), $key);

                return $element;
            }
        );

        $this->createService(...$this->createResolvers('assetServiceResolver', $element, $folder, $uniqueKeys))
            ->moveToFallbackFolder($element, $this->makeEmpty(UserInterface::class));

        $this->assertSame([], $expectedKeys);
    }

    public static function takenKeyProvider(): array
    {
        return [
            'key taken' => [['image_1.jpg', 'image.jpg_restore'], ['image.jpg_restore', 'image.jpg_restore']],
            'restore suffix taken too' => [
                ['image_1.jpg', 'image.jpg_restore_1'],
                ['image.jpg_restore', 'image.jpg_restore_1'],
            ],
        ];
    }

    public static function elementTypeProvider(): array
    {
        return [
            'asset' => [Asset::class, AssetFolder::class, 'assetServiceResolver'],
            'document' => [Document::class, DocumentFolder::class, 'documentServiceResolver'],
            'data object' => [Concrete::class, DataObjectFolder::class, 'dataObjectServiceResolver'],
        ];
    }

    /**
     * @throws Exception
     */
    public function testFailsWithTheMissingParentKeyWithoutCreatePermissionOnTheFallbackFolder(): void
    {
        $folder = $this->createMock(AssetFolder::class);
        $element = $this->createMock(Asset::class);
        $element->expects($this->never())->method('setParent');
        $element->expects($this->never())->method('setKey');

        $securityService = $this->createMock(SecurityServiceInterface::class);
        $securityService->method('hasElementPermission')->willThrowException(new ForbiddenException());

        $service = $this->createService(
            ...$this->createResolvers('assetServiceResolver', $element, $folder, ['my-key']),
            securityService: $securityService
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

        $service = $this->createService(assetServiceResolver: $assetServiceResolver);

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
        ElementInterface $folder,
        array $uniqueKeys
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

            $mock->expects($this->once())->method('createFolderByPath')->with('/__restored')->willReturn($folder);
            $mock->expects($this->atMost(2))
                ->method('getUniqueKey')
                ->with($element)
                ->willReturnOnConsecutiveCalls(...$uniqueKeys);
        }

        return $resolvers;
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
