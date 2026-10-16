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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Patcher\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\CoauthorService;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementIndexServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementSaveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\AdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\PatchService;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\SubTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\VersionCoauthor;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Pimcore\Model\Version\CoauthorContext;
use RuntimeException;
use function array_column;
use function array_map;
use function sprintf;
use function str_repeat;

/**
 * @internal
 */
final class PatchServiceTest extends Unit
{
    private Asset $element;

    private UserInterface $user;

    private CoauthorContext $coauthorContext;

    protected function _before(): void
    {
        $this->element = $this->makeEmpty(Asset::class);
        $this->user = $this->makeEmpty(UserInterface::class);
        $this->coauthorContext = new CoauthorContext();
    }

    public function testPatchActivatesCoauthorContextDuringSaveAndRestoresAfter(): void
    {
        $captured = [];
        $service = $this->createService(function () use (&$captured): void {
            $captured[] = [
                'active' => $this->coauthorContext->isActive(),
                'type' => $this->coauthorContext->getType(),
                'coauthor' => $this->coauthorContext->getCoauthor(),
            ];
        });

        $service->patchElement($this->element, ElementTypes::TYPE_ASSET, [
            'coauthorType' => 'agent',
            'coauthor' => 'product-data-agent',
        ], $this->user);

        $this->assertSame(
            ['active' => true, 'type' => 'agent', 'coauthor' => 'product-data-agent'],
            $captured[0]
        );
        $this->assertFalse($this->coauthorContext->isActive());
        $this->assertNull($this->coauthorContext->getType());
        $this->assertNull($this->coauthorContext->getCoauthor());
    }

    public function testPatchWithoutCoauthorKeysNeverActivatesContext(): void
    {
        $captured = [];
        $service = $this->createService(function () use (&$captured): void {
            $captured[] = $this->coauthorContext->isActive();
        });

        $service->patchElement($this->element, ElementTypes::TYPE_ASSET, [], $this->user);

        $this->assertSame([false], $captured);
        $this->assertFalse($this->coauthorContext->isActive());
    }

    public function testPatchWithPayloadCoauthorOverridesOuterContextAndRestoresItAfter(): void
    {
        $this->coauthorContext->set('agent', 'data-management');

        $captured = [];
        $service = $this->createService(function () use (&$captured): void {
            $captured[] = [
                'type' => $this->coauthorContext->getType(),
                'coauthor' => $this->coauthorContext->getCoauthor(),
            ];
        });

        $service->patchElement($this->element, ElementTypes::TYPE_ASSET, [
            'coauthorType' => 'automation',
            'coauthor' => 'import-job',
        ], $this->user);

        $this->assertSame(['type' => 'automation', 'coauthor' => 'import-job'], $captured[0]);
        $this->assertSame('agent', $this->coauthorContext->getType());
        $this->assertSame('data-management', $this->coauthorContext->getCoauthor());
    }

    public function testPatchRestoresContextWhenSaveThrows(): void
    {
        $service = $this->createService(static function (): void {
            throw new RuntimeException('boom');
        });

        try {
            $service->patchElement($this->element, ElementTypes::TYPE_ASSET, [
                'coauthorType' => 'agent',
                'coauthor' => 'product-data-agent',
            ], $this->user);
            $this->fail('Expected ElementSavingFailedException was not thrown');
        } catch (ElementSavingFailedException $e) {
            $this->assertStringContainsString('boom', $e->getMessage());
        }

        $this->assertFalse($this->coauthorContext->isActive());
        $this->assertNull($this->coauthorContext->getType());
        $this->assertNull($this->coauthorContext->getCoauthor());
    }

    public function testPatchRestoresOuterContextWhenSaveThrows(): void
    {
        $this->coauthorContext->set('agent', 'data-management');

        $service = $this->createService(static function (): void {
            throw new RuntimeException('boom');
        });

        try {
            $service->patchElement($this->element, ElementTypes::TYPE_ASSET, [
                'coauthorType' => 'automation',
                'coauthor' => 'import-job',
            ], $this->user);
            $this->fail('Expected ElementSavingFailedException was not thrown');
        } catch (ElementSavingFailedException) {
            // expected - assertions happen after the try/catch
        }

        $this->assertSame('agent', $this->coauthorContext->getType());
        $this->assertSame('data-management', $this->coauthorContext->getCoauthor());
    }

    public function testPatchWithEmptyStringCoauthorKeysNeverActivatesContext(): void
    {
        $captured = [];
        $service = $this->createService(function () use (&$captured): void {
            $captured[] = $this->coauthorContext->isActive();
        });

        $service->patchElement($this->element, ElementTypes::TYPE_ASSET, [
            'coauthorType' => '',
            'coauthor' => '',
        ], $this->user);

        $this->assertSame([false], $captured);
        $this->assertFalse($this->coauthorContext->isActive());
    }

    public function testPatchRejectsOverlongCoauthorTypeBeforeSaving(): void
    {
        $saved = false;
        $service = $this->createService(static function () use (&$saved): void {
            $saved = true;
        });

        $this->expectException(InvalidArgumentException::class);

        try {
            $service->patchElement($this->element, ElementTypes::TYPE_ASSET, [
                'coauthorType' => str_repeat('a', VersionCoauthor::MAX_TYPE_LENGTH + 1),
                'coauthor' => 'product-data-agent',
            ], $this->user);
        } finally {
            $this->assertFalse($saved);
            $this->assertFalse($this->coauthorContext->isActive());
        }
    }

    public function testPatchRejectsOverlongCoauthorBeforeSaving(): void
    {
        $saved = false;
        $service = $this->createService(static function () use (&$saved): void {
            $saved = true;
        });

        $this->expectException(InvalidArgumentException::class);

        try {
            $service->patchElement($this->element, ElementTypes::TYPE_ASSET, [
                'coauthorType' => 'agent',
                'coauthor' => str_repeat('a', VersionCoauthor::MAX_COAUTHOR_LENGTH + 1),
            ], $this->user);
        } finally {
            $this->assertFalse($saved);
            $this->assertFalse($this->coauthorContext->isActive());
        }
    }

    public function testPatchAcceptsCoauthorValuesAtTheLengthLimit(): void
    {
        $coauthorType = str_repeat('a', VersionCoauthor::MAX_TYPE_LENGTH);
        $coauthor = str_repeat('b', VersionCoauthor::MAX_COAUTHOR_LENGTH);

        $captured = [];
        $service = $this->createService(function () use (&$captured): void {
            $captured[] = [
                'type' => $this->coauthorContext->getType(),
                'coauthor' => $this->coauthorContext->getCoauthor(),
            ];
        });

        $service->patchElement($this->element, ElementTypes::TYPE_ASSET, [
            'coauthorType' => $coauthorType,
            'coauthor' => $coauthor,
        ], $this->user);

        $this->assertSame(['type' => $coauthorType, 'coauthor' => $coauthor], $captured[0]);
    }

    public function testGalleryAddKeepsCropHotspotsAndMarkerOfExistingImage(): void
    {
        $existing = $this->galleryItem(1, 10);
        $result = $this->handleGalleryPatch('add', [$this->galleryItem(1, 90), $this->galleryItem(3, 30)], [$existing]);

        $this->assertSame([$existing, $this->galleryItem(3, 30)], $result);
    }

    public function testGalleryAddKeepsRepeatedImages(): void
    {
        $result = $this->handleGalleryPatch(
            'add',
            [$this->galleryItem(3, 30)],
            [$this->galleryItem(1, 10), $this->galleryItem(1, 11), $this->galleryItem(2, 20)]
        );

        $this->assertSame([10, 11, 20, 30], $this->extractCropTops($result));
    }

    public function testGalleryReplaceStillUpdatesCropOfExistingImage(): void
    {
        $result = $this->handleGalleryPatch('replace', [$this->galleryItem(1, 90)], [$this->galleryItem(1, 10)]);

        $this->assertSame([$this->galleryItem(1, 90)], $result);
    }

    public function testGalleryRemoveDropsEveryOccurrenceOfImage(): void
    {
        $result = $this->handleGalleryPatch(
            'remove',
            [$this->galleryItem(1, 0)],
            [$this->galleryItem(1, 10), $this->galleryItem(2, 20), $this->galleryItem(1, 11)]
        );

        $this->assertSame([20], $this->extractCropTops($result));
    }

    /**
     * Core Hotspotimage::normalize() omits the image key when the item has no image.
     */
    public function testGalleryItemWithoutImageIsKeptOnAddAndRemove(): void
    {
        $withoutImage = $this->galleryItem(1, 10);
        unset($withoutImage['image']);
        $existing = [$withoutImage, $this->galleryItem(2, 20)];

        $added = $this->handleGalleryPatch('add', [$this->galleryItem(3, 30)], $existing);
        $removed = $this->handleGalleryPatch('remove', [$this->galleryItem(2, 0)], $existing);

        $this->assertSame([$withoutImage, $this->galleryItem(2, 20), $this->galleryItem(3, 30)], $added);
        $this->assertSame([$withoutImage], $removed);
    }

    public function testAddAppendsEntriesWithoutElement(): void
    {
        $withoutImage = $this->galleryItem(3, 30);
        unset($withoutImage['image']);

        $result = $this->handleGalleryPatch('add', [$withoutImage, $withoutImage], [$this->galleryItem(1, 10)]);

        $this->assertSame([$this->galleryItem(1, 10), $withoutImage, $withoutImage], $result);
    }

    public function testAddAppendsRepeatedNewElementOnlyWhenRepeatedEntriesAreAllowed(): void
    {
        $patch = ['action' => 'add', 'data' => [['id' => 3, 'type' => 'object'], ['id' => 3, 'type' => 'object']]];
        $service = $this->createService(static fn () => null);

        $this->assertCount(2, $service->handlePatchDataField($patch, []));
        $this->assertCount(1, $service->handlePatchDataField($patch, [], null, false));
    }

    public function testRepeatedExistingEntriesAreKeptOnceWhenRepeatedEntriesAreNotAllowed(): void
    {
        $existing = [['id' => 1, 'type' => 'object'], ['id' => 2, 'type' => 'object'], ['id' => 1, 'type' => 'object']];
        $service = $this->createService(static fn () => null);

        $added = $service->handlePatchDataField(
            ['action' => 'add', 'data' => [['id' => 3, 'type' => 'object']]],
            $existing,
            null,
            false
        );
        $removed = $service->handlePatchDataField(
            ['action' => 'remove', 'data' => [['id' => 2, 'type' => 'object']]],
            $existing,
            null,
            false
        );

        $this->assertSame([[1, 'object'], [2, 'object'], [3, 'object']], $this->extractIdsAndTypes($added));
        $this->assertSame([[1, 'object']], $this->extractIdsAndTypes($removed));
    }

    /**
     * The Studio batch edit form sends data null when "Add"/"Remove" is chosen without selecting anything.
     */
    public function testPatchWithoutDataKeepsExistingEntries(): void
    {
        $existing = [$this->galleryItem(1, 10)];

        $this->assertSame($existing, $this->handleGalleryPatch('add', null, $existing));
        $this->assertSame($existing, $this->handleGalleryPatch('remove', null, $existing));
        $this->assertSame([], $this->handleGalleryPatch('add', null, []));
    }

    public function testPatchWithoutDataKeyKeepsExistingEntries(): void
    {
        $existing = [['id' => 1, 'type' => 'object']];

        $result = $this->createService(static fn () => null)->handlePatchDataField(['action' => 'add'], $existing);

        $this->assertSame($existing, $result);
    }

    public function testDataObjectTypeMatchesObjectEntries(): void
    {
        $existing = [['id' => 1, 'type' => 'object'], ['id' => 2, 'type' => 'object']];
        $service = $this->createService(static fn () => null);

        $added = $service->handlePatchDataField(
            ['action' => 'add', 'data' => [['id' => 2, 'type' => 'data-object']]],
            $existing
        );
        $removed = $service->handlePatchDataField(
            ['action' => 'remove', 'data' => [['id' => 1, 'type' => 'data-object']]],
            $existing
        );

        $this->assertSame($existing, $added);
        $this->assertSame([[2, 'object']], $this->extractIdsAndTypes($removed));
    }

    public function testPlainListIsReturnedUnchanged(): void
    {
        $value = [$this->galleryItem(2, 20)];

        $result = $this->createService(static fn () => null)->handlePatchDataField(
            $value,
            [$this->galleryItem(1, 10)],
            SubTypes::IMAGE->value
        );

        $this->assertSame($value, $result);
    }

    public function testPatchWithoutActionReplacesWithData(): void
    {
        $data = [$this->galleryItem(2, 20)];

        $this->assertSame($data, $this->handleGalleryPatch(null, $data, [$this->galleryItem(1, 10)]));
    }

    public function testReplaceWithoutDataClearsField(): void
    {
        $this->assertSame([], $this->handleGalleryPatch('replace', null, [$this->galleryItem(1, 10)]));
    }

    public function testAddOfAlreadyRelatedElementCreatesNoDuplicate(): void
    {
        $existing = [['id' => 1, 'type' => 'object'], ['id' => 2, 'type' => 'object']];

        $result = $this->createService(static fn () => null)->handlePatchDataField(
            ['action' => 'add', 'data' => [['id' => 2, 'type' => 'object'], ['id' => 3, 'type' => 'asset']]],
            $existing
        );

        $this->assertSame([[1, 'object'], [2, 'object'], [3, 'asset']], $this->extractIdsAndTypes($result));
    }

    public function testRemoveOfSimpleRelationKeepsOtherElements(): void
    {
        $existing = [['id' => 1, 'type' => 'object'], ['id' => 2, 'type' => 'object'], ['id' => 1, 'type' => 'asset']];

        $result = $this->createService(static fn () => null)->handlePatchDataField(
            ['action' => 'remove', 'data' => [['id' => 1, 'type' => 'object']]],
            $existing
        );

        $this->assertSame([[2, 'object'], [1, 'asset']], $this->extractIdsAndTypes($result));
    }

    private function handleGalleryPatch(?string $action, ?array $newData, array $existing): array
    {
        $patch = ['data' => $newData];
        if ($action !== null) {
            $patch['action'] = $action;
        }

        return $this->createService(static fn () => null)->handlePatchDataField(
            $patch,
            $existing,
            SubTypes::IMAGE->value
        );
    }

    /**
     * Shape produced by core Hotspotimage::normalize(), the crop top identifies the item in assertions.
     */
    private function galleryItem(int $imageId, int $cropTop): array
    {
        return [
            'hotspots' => [['name' => 'spot', 'top' => $cropTop, 'left' => 5, 'width' => 10, 'height' => 10]],
            'marker' => [['name' => 'pin', 'top' => $cropTop, 'left' => 1]],
            'crop' => ['cropTop' => $cropTop, 'cropLeft' => 0, 'cropWidth' => 50, 'cropHeight' => 50],
            'image' => ['type' => 'asset', 'id' => $imageId],
        ];
    }

    private function extractCropTops(array $items): array
    {
        return array_column(array_column($items, 'crop'), 'cropTop');
    }

    private function extractIdsAndTypes(array $items): array
    {
        return array_map(static fn (array $item) => [$item['id'], $item['type']], $items);
    }

    /**
     * GHSA-wp52-4qcp-x4jx: a data object folder patch without `editableData` (e.g. a bare
     * `{"key": "renamed"}`) skipped the `save` permission check entirely, allowing any user with
     * the `objects` permission to rename/move/lock/set properties on folders and objects outside
     * their workspace write permissions.
     */
    public function testPatchOfDataObjectFolderRequiresSavePermission(): void
    {
        $folder = $this->makeEmpty(Folder::class);
        $saved = false;
        $checkedPermissions = [];

        $service = $this->createServiceForElement(
            $folder,
            static function () use (&$saved): void {
                $saved = true;
            },
            static function (ElementInterface $element, UserInterface $user, string $permission) use (&$checkedPermissions): void {
                $checkedPermissions[] = $permission;

                throw new ForbiddenException(sprintf('You dont have %s permission', $permission));
            }
        );

        try {
            $service->patchElement($folder, ElementTypes::TYPE_OBJECT, ['key' => 'renamed'], $this->user);
            $this->fail('Expected ForbiddenException was not thrown');
        } catch (ForbiddenException) {
            // expected - assertions happen after the try/catch
        }

        $this->assertSame(['save'], $checkedPermissions);
        $this->assertFalse($saved);
    }

    /**
     * Legitimate patches of a folder/object must still succeed once the user actually holds `save`.
     */
    public function testPatchOfDataObjectFolderSucceedsWithSavePermission(): void
    {
        $folder = $this->makeEmpty(Folder::class);
        $saved = false;
        $checkedPermissions = [];

        $service = $this->createServiceForElement(
            $folder,
            static function () use (&$saved): void {
                $saved = true;
            },
            static function (ElementInterface $element, UserInterface $user, string $permission) use (&$checkedPermissions): void {
                $checkedPermissions[] = $permission;
            }
        );

        $service->patchElement($folder, ElementTypes::TYPE_OBJECT, ['key' => 'renamed'], $this->user);

        $this->assertSame(['save'], $checkedPermissions);
        $this->assertTrue($saved);
    }

    /**
     * A pure publish/unpublish task is gated by PublishAdapter itself, so it must not additionally
     * require the workspace `save` permission.
     */
    public function testPatchWithPublishTaskDoesNotRequireSavePermission(): void
    {
        $folder = $this->makeEmpty(Folder::class);
        $saved = false;
        $checkedPermissions = [];

        $service = $this->createServiceForElement(
            $folder,
            static function () use (&$saved): void {
                $saved = true;
            },
            static function (ElementInterface $element, UserInterface $user, string $permission) use (&$checkedPermissions): void {
                $checkedPermissions[] = $permission;
            }
        );

        $service->patchElement($folder, ElementTypes::TYPE_OBJECT, ['task' => 'publish'], $this->user);

        $this->assertSame([], $checkedPermissions);
        $this->assertTrue($saved);
    }

    private function createService(callable $onSave): PatchService
    {
        return new PatchService(
            adapterLoader: $this->makeEmpty(AdapterLoaderInterface::class, [
                'loadAdapters' => [],
            ]),
            dataAdapterService: $this->makeEmpty(DataAdapterServiceInterface::class),
            elementService: $this->makeEmpty(ElementServiceInterface::class),
            jobExecutionAgent: $this->makeEmpty(JobExecutionAgentInterface::class),
            indexService: $this->makeEmpty(ElementIndexServiceInterface::class),
            elementSaveService: $this->makeEmpty(ElementSaveServiceInterface::class, [
                'save' => $onSave,
            ]),
            securityService: $this->makeEmpty(SecurityServiceInterface::class),
            coauthorService: new CoauthorService($this->coauthorContext),
        );
    }

    private function createServiceForElement(
        ElementInterface $element,
        callable $onSave,
        callable $hasElementPermission
    ): PatchService {
        return new PatchService(
            adapterLoader: $this->makeEmpty(AdapterLoaderInterface::class, [
                'loadAdapters' => [],
            ]),
            dataAdapterService: $this->makeEmpty(DataAdapterServiceInterface::class),
            elementService: $this->makeEmpty(ElementServiceInterface::class),
            jobExecutionAgent: $this->makeEmpty(JobExecutionAgentInterface::class),
            indexService: $this->makeEmpty(ElementIndexServiceInterface::class),
            elementSaveService: $this->makeEmpty(ElementSaveServiceInterface::class, [
                'save' => $onSave,
            ]),
            securityService: $this->makeEmpty(SecurityServiceInterface::class, [
                'hasElementPermission' => $hasElementPermission,
            ]),
            coauthorService: new CoauthorService($this->coauthorContext),
        );
    }
}
