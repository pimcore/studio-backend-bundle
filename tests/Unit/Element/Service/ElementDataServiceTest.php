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
use Pimcore\Event\ElementEvents;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\FactoryInterface;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use Pimcore\Resolver\ResolverInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_merge;
use function get_class;

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
        $service = $this->createService(securityService: $this->securityServiceWithoutUser());

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
        $service = $this->createService(securityService: $securityService);

        $data = $service->getRelatedElementData($this->makeAsset(isAllowed: true, calls: $calls));

        $this->assertFalse($data->getHasViewAccess());
        $this->assertSame([], $calls);
    }

    /**
     * @throws Exception
     */
    public function testSummaryIsCheckedOnAModelOfTheElementClassWithoutLoadingTheElement(): void
    {
        $folder = $this->makeFolder(isAllowed: true, calls: $calls);
        $service = $this->createService(
            serviceResolver: $this->serviceResolverThatMustNotLoad(),
            modelFactory: $this->modelFactoryBuilding(Folder::class, $folder, $built)
        );

        $data = $service->getRelatedElementDataFromSummary($this->makeSummary(['className' => null, 'subtype' => 'folder']));

        $this->assertSame([Folder::class], $built);
        $this->assertSame([[ElementPermissions::VIEW_PERMISSION, true]], $calls);
        $this->assertTrue($data->getHasViewAccess());
        $this->assertSame(83, $data->getId());
        $this->assertSame('object', $data->getType());
        $this->assertSame('folder', $data->getSubtype());
        $this->assertSame('/path/to/element', $data->getFullPath());
        $this->assertNull($data->getIsPublished());
    }

    /**
     * @throws Exception
     */
    public function testDeniedViewOnTheModelIsReported(): void
    {
        $folder = $this->makeFolder(isAllowed: false, calls: $calls);
        $service = $this->createService(
            serviceResolver: $this->serviceResolverThatMustNotLoad(),
            modelFactory: $this->modelFactoryBuilding(Folder::class, $folder, $built)
        );

        $data = $service->getRelatedElementDataFromSummary($this->makeSummary(['className' => null, 'subtype' => 'folder']));

        $this->assertFalse($data->getHasViewAccess());
    }

    /**
     * @throws Exception
     */
    public function testAssetSummaryUsesTheClassOfItsType(): void
    {
        $image = $this->makeAsset(isAllowed: true, calls: $calls);
        $assetClassResolver = $this->makeEmpty(ResolverInterface::class, [
            'resolve' => function (string $type) use (&$resolved): ?string {
                $resolved[] = $type;

                return Asset\Image::class;
            },
        ]);
        $service = $this->createService(
            serviceResolver: $this->serviceResolverThatMustNotLoad(),
            assetClassResolver: $assetClassResolver,
            modelFactory: $this->modelFactoryBuilding(Asset\Image::class, $image, $built)
        );

        $data = $service->getRelatedElementDataFromSummary(
            $this->makeSummary(['type' => 'asset', 'className' => null, 'subtype' => 'image', 'published' => true])
        );

        $this->assertSame(['image'], $resolved);
        $this->assertSame([Asset\Image::class], $built);
        $this->assertTrue($data->getHasViewAccess());
        $this->assertSame('asset', $data->getType());
        $this->assertSame('image', $data->getSubtype());
        $this->assertNull($data->getIsPublished());
    }

    /**
     * @throws Exception
     */
    public function testDocumentSummaryKeepsThePublishedState(): void
    {
        $service = $this->createService(securityService: $this->securityServiceWithoutUser());

        $data = $service->getRelatedElementDataFromSummary(
            $this->makeSummary(['type' => 'document', 'className' => null, 'subtype' => 'page', 'published' => false])
        );

        $this->assertSame('document', $data->getType());
        $this->assertSame('page', $data->getSubtype());
        $this->assertFalse($data->getIsPublished());
    }

    /**
     * @throws Exception
     */
    public function testElementIsLoadedWhenAWorkflowAppliesToIt(): void
    {
        $loaded = $this->makeAsset(isAllowed: true, calls: $calls);
        $service = $this->createService(
            serviceResolver: $this->serviceResolverLoading($loaded, $loads),
            modelFactory: $this->modelFactoryBuilding(Folder::class, $this->makeFolder(isAllowed: true, calls: $modelCalls)),
            workflows: ['publishing' => ['enabled' => true, 'supports' => [Folder::class], 'places' => []]]
        );

        $data = $service->getRelatedElementDataFromSummary($this->makeSummary(['className' => null, 'subtype' => 'folder']));

        $this->assertSame([['object', 83]], $loads);
        $this->assertSame([], $modelCalls);
        $this->assertSame('/path/to/asset.jpg', $data->getFullPath());
        $this->assertTrue($data->getHasViewAccess());
    }

    /**
     * @throws Exception
     */
    public function testElementIsNotLoadedWhenNoWorkflowAppliesToIt(): void
    {
        $service = $this->createService(
            serviceResolver: $this->serviceResolverThatMustNotLoad(),
            modelFactory: $this->modelFactoryBuilding(Folder::class, $this->makeFolder(isAllowed: true, calls: $calls)),
            workflows: ['publishing' => ['enabled' => true, 'supports' => [Asset\Image::class], 'places' => []]]
        );

        $data = $service->getRelatedElementDataFromSummary($this->makeSummary(['className' => null, 'subtype' => 'folder']));

        $this->assertSame([[ElementPermissions::VIEW_PERMISSION, true]], $calls);
        $this->assertTrue($data->getHasViewAccess());
    }

    /**
     * @throws Exception
     */
    public function testElementIsLoadedWhenAnExpressionStrategyTargetsItsClass(): void
    {
        $service = $this->createService(
            serviceResolver: $this->serviceResolverLoading($this->makeAsset(isAllowed: false, calls: $calls), $loads),
            modelFactory: $this->modelFactoryBuilding(Folder::class, $this->makeFolder(isAllowed: true, calls: $modelCalls)),
            workflows: ['publishing' => ['enabled' => true, 'supports' => [], 'support_strategy' => ['type' => 'expression', 'arguments' => ['\\Pimcore\\Model\\DataObject\\Folder', 'subject.getPath() matches "#^/new#"']], 'places' => []]]
        );

        $data = $service->getRelatedElementDataFromSummary($this->makeSummary(['className' => null, 'subtype' => 'folder']));

        $this->assertSame([['object', 83]], $loads);
        $this->assertSame([], $modelCalls);
        $this->assertFalse($data->getHasViewAccess());
    }

    /**
     * @throws Exception
     */
    public function testDisabledWorkflowsDoNotApply(): void
    {
        $service = $this->createService(
            serviceResolver: $this->serviceResolverThatMustNotLoad(),
            modelFactory: $this->modelFactoryBuilding(Folder::class, $this->makeFolder(isAllowed: true, calls: $calls)),
            workflows: ['publishing' => ['enabled' => false, 'supports' => [Folder::class], 'places' => []]]
        );

        $data = $service->getRelatedElementDataFromSummary($this->makeSummary(['className' => null, 'subtype' => 'folder']));

        $this->assertSame([[ElementPermissions::VIEW_PERMISSION, true]], $calls);
        $this->assertTrue($data->getHasViewAccess());
    }

    /**
     * @throws Exception
     */
    public function testElementIsLoadedWhenItsModelClassIsOverridden(): void
    {
        $service = $this->createService(
            serviceResolver: $this->serviceResolverLoading($this->makeAsset(isAllowed: false, calls: $calls), $loads),
            modelFactory: $this->modelFactoryBuilding(
                Folder::class,
                $this->makeFolder(isAllowed: true, calls: $modelCalls),
                classMap: [Folder::class => 'App\\Model\\DataObject\\Folder']
            )
        );

        $data = $service->getRelatedElementDataFromSummary($this->makeSummary(['className' => null, 'subtype' => 'folder']));

        $this->assertSame([['object', 83]], $loads);
        $this->assertSame([], $modelCalls);
        $this->assertFalse($data->getHasViewAccess());
    }

    /**
     * @throws Exception
     */
    public function testElementIsNotLoadedWhenAnExpressionStrategyTargetsAnotherClass(): void
    {
        $service = $this->createService(
            serviceResolver: $this->serviceResolverThatMustNotLoad(),
            modelFactory: $this->modelFactoryBuilding(Folder::class, $this->makeFolder(isAllowed: true, calls: $calls)),
            workflows: ['assets' => ['enabled' => true, 'supports' => [], 'support_strategy' => ['type' => 'expression', 'arguments' => ['\\Pimcore\\Model\\Asset', 'is_fully_authenticated()']], 'places' => []]]
        );

        $data = $service->getRelatedElementDataFromSummary($this->makeSummary(['className' => null, 'subtype' => 'folder']));

        $this->assertSame([[ElementPermissions::VIEW_PERMISSION, true]], $calls);
        $this->assertTrue($data->getHasViewAccess());
    }

    /**
     * @throws Exception
     */
    public function testElementIsLoadedWhenAWorkflowUsesACustomSupportStrategy(): void
    {
        $service = $this->createService(
            serviceResolver: $this->serviceResolverLoading($this->makeAsset(isAllowed: false, calls: $calls), $loads),
            modelFactory: $this->modelFactoryBuilding(Folder::class, $this->makeFolder(isAllowed: true, calls: $modelCalls)),
            workflows: ['custom' => ['enabled' => true, 'supports' => [], 'support_strategy' => ['service' => 'app.workflow_support'], 'places' => []]]
        );

        $data = $service->getRelatedElementDataFromSummary($this->makeSummary(['className' => null, 'subtype' => 'folder']));

        $this->assertSame([['object', 83]], $loads);
        $this->assertSame([], $modelCalls);
        $this->assertFalse($data->getHasViewAccess());
    }

    /**
     * @throws Exception
     */
    public function testElementIsLoadedWhenAClassOfTheProjectDecidesAboutPermissions(): void
    {
        $customClass = get_class(new class() extends Asset\Image {
            public function isAllowed(string $type, ?User $user = null): bool
            {
                return $this->getCustomSetting('restricted') !== true;
            }
        });
        $assetClassResolver = $this->makeEmpty(ResolverInterface::class, ['resolve' => $customClass]);
        $service = $this->createService(
            serviceResolver: $this->serviceResolverLoading($this->makeAsset(isAllowed: false, calls: $calls), $loads),
            assetClassResolver: $assetClassResolver,
            modelFactory: $this->modelFactoryBuilding($customClass, $this->makeAsset(isAllowed: true, calls: $modelCalls))
        );

        $data = $service->getRelatedElementDataFromSummary(
            $this->makeSummary(['type' => 'asset', 'className' => null, 'subtype' => 'image'])
        );

        $this->assertSame([['asset', 83]], $loads);
        $this->assertSame([], $modelCalls);
        $this->assertFalse($data->getHasViewAccess());
    }

    /**
     * @throws Exception
     */
    public function testElementIsLoadedWhenAListenerDecidesAboutPermissions(): void
    {
        $service = $this->createService(
            serviceResolver: $this->serviceResolverLoading($this->makeAsset(isAllowed: false, calls: $calls), $loads),
            modelFactory: $this->modelFactoryBuilding(Folder::class, $this->makeFolder(isAllowed: true, calls: $modelCalls)),
            hasPermissionListeners: true
        );

        $data = $service->getRelatedElementDataFromSummary($this->makeSummary(['className' => null, 'subtype' => 'folder']));

        $this->assertSame([['object', 83]], $loads);
        $this->assertSame([], $modelCalls);
        $this->assertFalse($data->getHasViewAccess());
    }

    /**
     * @throws Exception
     */
    public function testSummaryOfUnknownClassIsResolvedThroughTheElement(): void
    {
        $service = $this->createService(
            serviceResolver: $this->serviceResolverLoading($this->makeAsset(isAllowed: true, calls: $calls), $loads)
        );

        $data = $service->getRelatedElementDataFromSummary(
            $this->makeSummary(['className' => 'ClassThatDoesNotExistAnymore'])
        );

        $this->assertSame([['object', 83]], $loads);
        $this->assertTrue($data->getHasViewAccess());
        $this->assertSame([[ElementPermissions::VIEW_PERMISSION, true]], $calls);
        $this->assertSame('/path/to/asset.jpg', $data->getFullPath());
    }

    /**
     * @throws Exception
     */
    public function testSummaryOfVanishedElementHasNoViewAccess(): void
    {
        $service = $this->createService(serviceResolver: $this->makeEmpty(ServiceResolverInterface::class, ['getElementById' => null]));

        $data = $service->getRelatedElementDataFromSummary(
            $this->makeSummary(['className' => 'ClassThatDoesNotExistAnymore'])
        );

        $this->assertFalse($data->getHasViewAccess());
        $this->assertSame('ClassThatDoesNotExistAnymore', $data->getSubtype());
        $this->assertSame('/path/to/element', $data->getFullPath());
    }

    /**
     * @param array<string, array<string, mixed>> $workflows the workflow configuration, keyed by workflow name
     *
     * @throws Exception
     */
    private function createService(
        ?SecurityServiceInterface $securityService = null,
        ?ServiceResolverInterface $serviceResolver = null,
        ?ResolverInterface $assetClassResolver = null,
        ?FactoryInterface $modelFactory = null,
        array $workflows = [],
        bool $hasPermissionListeners = false,
    ): ElementDataService {
        $securityService ??= $this->makeEmpty(SecurityServiceInterface::class, ['getCurrentUser' => new User()]);
        $eventDispatcher = $this->makeEmpty(EventDispatcherInterface::class, [
            'hasListeners' => function (?string $eventName) use ($hasPermissionListeners): bool {
                $this->assertSame(ElementEvents::ELEMENT_PERMISSION_IS_ALLOWED, $eventName);

                return $hasPermissionListeners;
            },
        ]);

        return new ElementDataService(
            $securityService,
            $serviceResolver ?? $this->makeEmpty(ServiceResolverInterface::class),
            $assetClassResolver ?? $this->makeEmpty(ResolverInterface::class, ['resolve' => null]),
            $this->makeEmpty(ResolverInterface::class, ['resolve' => null]),
            $modelFactory ?? $this->makeEmpty(FactoryInterface::class, ['getClassMap' => []]),
            $eventDispatcher,
            $workflows
        );
    }

    /**
     * @throws Exception
     */
    private function securityServiceWithoutUser(): SecurityServiceInterface
    {
        return $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => static function (): UserInterface {
                throw new UserNotFoundException();
            },
        ]);
    }

    /**
     * @throws Exception
     */
    private function serviceResolverThatMustNotLoad(): ServiceResolverInterface
    {
        return $this->makeEmpty(ServiceResolverInterface::class, [
            'getElementById' => function (): ?ElementInterface {
                $this->fail('the element must not be loaded');
            },
        ]);
    }

    /**
     * @param array<int, array{string, int}>|null $loads
     *
     * @throws Exception
     */
    private function serviceResolverLoading(ElementInterface $element, ?array &$loads = null): ServiceResolverInterface
    {
        $loads = [];

        return $this->makeEmpty(ServiceResolverInterface::class, [
            'getElementById' => function (string $type, int $id) use (&$loads, $element): ElementInterface {
                $loads[] = [$type, $id];

                return $element;
            },
        ]);
    }

    /**
     * @param string[]|null $built
     *
     * @throws Exception
     */
    private function modelFactoryBuilding(
        string $expectedClass,
        ElementInterface $model,
        ?array &$built = null,
        array $classMap = []
    ): FactoryInterface {
        $built = [];

        return $this->makeEmpty(FactoryInterface::class, [
            'getClassMap' => $classMap,
            'build' => function (string $name) use (&$built, $expectedClass, $model) {
                $built[] = $name;
                $this->assertSame($expectedClass, $name);

                return $model;
            },
        ]);
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

    /**
     * @param array<int, array{string, bool}>|null $calls
     *
     * @throws Exception
     */
    private function makeFolder(bool $isAllowed, ?array &$calls = null): Folder
    {
        $calls = [];

        return $this->makeEmpty(Folder::class, [
            'isAllowed' => function (string $type, ?User $user = null) use (&$calls, $isAllowed): bool {
                $calls[] = [$type, $user instanceof User];

                return $isAllowed;
            },
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array{id: int, parentId: int, type: string, subtype: string, className: string|null, fullpath: string, filename: string, published: bool}
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
}
