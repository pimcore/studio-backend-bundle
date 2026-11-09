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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Updater\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface as DataObjectDataService;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\DataServiceInterface as DocumentDataService;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\CoauthorService;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementIndexServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementSaveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Updater\Service\AdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Updater\Service\UpdateService;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementSaveTasks;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\VersionCoauthor;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Pimcore\Model\Version\CoauthorContext;
use RuntimeException;
use function sprintf;
use function str_repeat;

/**
 * @internal
 */
final class UpdateServiceTest extends Unit
{
    private const int ELEMENT_ID = 42;

    private Asset $element;

    private CoauthorContext $coauthorContext;

    protected function _before(): void
    {
        $this->element = $this->makeEmpty(Asset::class);
        $this->coauthorContext = new CoauthorContext();
    }

    public function testUpdateActivatesCoauthorContextDuringSaveAndRestoresAfter(): void
    {
        $captured = [];
        $service = $this->createService(function () use (&$captured): void {
            $captured[] = [
                'active' => $this->coauthorContext->isActive(),
                'type' => $this->coauthorContext->getType(),
                'coauthor' => $this->coauthorContext->getCoauthor(),
            ];
        });

        $service->update(ElementTypes::TYPE_ASSET, self::ELEMENT_ID, [
            'coauthorType' => 'agent',
            'coauthor' => 'product-data-agent',
        ]);

        $this->assertSame(
            ['active' => true, 'type' => 'agent', 'coauthor' => 'product-data-agent'],
            $captured[0]
        );
        $this->assertFalse($this->coauthorContext->isActive());
        $this->assertNull($this->coauthorContext->getType());
        $this->assertNull($this->coauthorContext->getCoauthor());
    }

    public function testUpdateWithoutCoauthorKeysNeverActivatesContext(): void
    {
        $captured = [];
        $service = $this->createService(function () use (&$captured): void {
            $captured[] = $this->coauthorContext->isActive();
        });

        $service->update(ElementTypes::TYPE_ASSET, self::ELEMENT_ID, []);

        $this->assertSame([false], $captured);
        $this->assertFalse($this->coauthorContext->isActive());
    }

    public function testUpdateWithPayloadCoauthorOverridesOuterContextAndRestoresItAfter(): void
    {
        $this->coauthorContext->set('agent', 'data-management');

        $captured = [];
        $service = $this->createService(function () use (&$captured): void {
            $captured[] = [
                'type' => $this->coauthorContext->getType(),
                'coauthor' => $this->coauthorContext->getCoauthor(),
            ];
        });

        $service->update(ElementTypes::TYPE_ASSET, self::ELEMENT_ID, [
            'coauthorType' => 'automation',
            'coauthor' => 'import-job',
        ]);

        $this->assertSame(['type' => 'automation', 'coauthor' => 'import-job'], $captured[0]);
        $this->assertSame('agent', $this->coauthorContext->getType());
        $this->assertSame('data-management', $this->coauthorContext->getCoauthor());
    }

    public function testUpdateRestoresContextWhenSaveThrows(): void
    {
        $service = $this->createService(static function (): void {
            throw new RuntimeException('boom');
        });

        try {
            $service->update(ElementTypes::TYPE_ASSET, self::ELEMENT_ID, [
                'coauthorType' => 'agent',
                'coauthor' => 'product-data-agent',
            ]);
            $this->fail('Expected ElementSavingFailedException was not thrown');
        } catch (ElementSavingFailedException $e) {
            $this->assertStringContainsString('boom', $e->getMessage());
        }

        $this->assertFalse($this->coauthorContext->isActive());
        $this->assertNull($this->coauthorContext->getType());
        $this->assertNull($this->coauthorContext->getCoauthor());
    }

    public function testUpdateRestoresOuterContextWhenSaveThrows(): void
    {
        $this->coauthorContext->set('agent', 'data-management');

        $service = $this->createService(static function (): void {
            throw new RuntimeException('boom');
        });

        try {
            $service->update(ElementTypes::TYPE_ASSET, self::ELEMENT_ID, [
                'coauthorType' => 'automation',
                'coauthor' => 'import-job',
            ]);
            $this->fail('Expected ElementSavingFailedException was not thrown');
        } catch (ElementSavingFailedException) {
            // expected - assertions happen after the try/catch
        }

        $this->assertSame('agent', $this->coauthorContext->getType());
        $this->assertSame('data-management', $this->coauthorContext->getCoauthor());
    }

    public function testUpdateWithEmptyStringCoauthorKeysNeverActivatesContext(): void
    {
        $captured = [];
        $service = $this->createService(function () use (&$captured): void {
            $captured[] = $this->coauthorContext->isActive();
        });

        $service->update(ElementTypes::TYPE_ASSET, self::ELEMENT_ID, [
            'coauthorType' => '',
            'coauthor' => '',
        ]);

        $this->assertSame([false], $captured);
        $this->assertFalse($this->coauthorContext->isActive());
    }

    public function testUpdateWithOnlyCoauthorTypeNeverActivatesContext(): void
    {
        $captured = [];
        $service = $this->createService(function () use (&$captured): void {
            $captured[] = $this->coauthorContext->isActive();
        });

        $service->update(ElementTypes::TYPE_ASSET, self::ELEMENT_ID, [
            'coauthorType' => 'agent',
        ]);

        $this->assertSame([false], $captured);
        $this->assertFalse($this->coauthorContext->isActive());
    }

    public function testUpdateRejectsOverlongCoauthorTypeBeforeSaving(): void
    {
        $saved = false;
        $service = $this->createService(static function () use (&$saved): void {
            $saved = true;
        });

        $this->expectException(InvalidArgumentException::class);

        try {
            $service->update(ElementTypes::TYPE_ASSET, self::ELEMENT_ID, [
                'coauthorType' => str_repeat('a', VersionCoauthor::MAX_TYPE_LENGTH + 1),
                'coauthor' => 'product-data-agent',
            ]);
        } finally {
            $this->assertFalse($saved);
            $this->assertFalse($this->coauthorContext->isActive());
        }
    }

    public function testUpdateRejectsOverlongCoauthorBeforeSaving(): void
    {
        $saved = false;
        $service = $this->createService(static function () use (&$saved): void {
            $saved = true;
        });

        $this->expectException(InvalidArgumentException::class);

        try {
            $service->update(ElementTypes::TYPE_ASSET, self::ELEMENT_ID, [
                'coauthorType' => 'agent',
                'coauthor' => str_repeat('b', VersionCoauthor::MAX_COAUTHOR_LENGTH + 1),
            ]);
        } finally {
            $this->assertFalse($saved);
            $this->assertFalse($this->coauthorContext->isActive());
        }
    }

    /**
     * Writing a data object folder requires the workspace `save` permission.
     */
    public function testUpdateOfDataObjectFolderRequiresSavePermission(): void
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
            $service->update(ElementTypes::TYPE_DATA_OBJECT, self::ELEMENT_ID, ['properties' => []]);
            $this->fail('Expected ForbiddenException was not thrown');
        } catch (ForbiddenException) {
            // expected - assertions happen after the try/catch
        }

        $this->assertSame(['save'], $checkedPermissions);
        $this->assertFalse($saved);
    }

    /**
     * Folders have no publish state, so a publish/unpublish task must not skip the `save` check.
     */
    public function testUpdateOfDataObjectFolderWithPublishTaskStillRequiresSavePermission(): void
    {
        foreach (['publish', 'unpublish'] as $task) {
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
                $service->update(ElementTypes::TYPE_DATA_OBJECT, self::ELEMENT_ID, ['task' => $task]);
                $this->fail('Expected ForbiddenException was not thrown');
            } catch (ForbiddenException) {
                // expected - assertions happen after the try/catch
            }

            $this->assertSame(['save'], $checkedPermissions, $task);
            $this->assertFalse($saved, $task);
        }
    }

    /**
     * A publish/unpublish task on a data object needs the publish/unpublish permission, checked before any
     * data is applied, and no `save` permission; the edited data may travel with it (as in the classic admin).
     *
     * @dataProvider publicationPayloadProvider
     */
    public function testUpdateOfDataObjectWithPublicationTaskNeedsOnlyThePublicationPermission(
        array $data,
        string $expectedPermission
    ): void {
        $object = $this->makeEmpty(Concrete::class);
        $saved = false;
        $checkedPermissions = [];

        $service = $this->createServiceForElement(
            $object,
            static function () use (&$saved): void {
                $saved = true;
            },
            static function (ElementInterface $element, UserInterface $user, string $permission) use (&$checkedPermissions): void {
                $checkedPermissions[] = $permission;
            }
        );

        $service->update(ElementTypes::TYPE_DATA_OBJECT, self::ELEMENT_ID, $data);

        $this->assertSame([$expectedPermission], $checkedPermissions);
        $this->assertTrue($saved);
    }

    public static function publicationPayloadProvider(): array
    {
        return [
            'publish task' => [['task' => 'publish'], 'publish'],
            'unpublish task' => [['task' => 'unpublish'], 'unpublish'],
            'publish task with published flag' => [['task' => 'publish', 'published' => true], 'publish'],
            'publish task with coauthor' => [['task' => 'publish', 'coauthorType' => 'agent', 'coauthor' => 'a'], 'publish'],
            'publish task with draft data' => [['task' => 'publish', 'useDraftData' => false], 'publish'],
            'publish task with editableData' => [['task' => 'publish', 'editableData' => ['title' => 'changed']], 'publish'],
            'unpublish task with editableData' => [['task' => 'unpublish', 'editableData' => ['title' => 'x']], 'unpublish'],
            'publish task with editableData and draft data' => [
                ['task' => 'publish', 'useDraftData' => true, 'editableData' => ['title' => 'x']],
                'publish',
            ],
        ];
    }

    /**
     * Metadata that travels with a publish/unpublish task needs its own permission; anything not
     * mapped needs `save`. Without the permission nothing is saved.
     *
     * @dataProvider publicationMetadataProvider
     */
    public function testUpdateOfDataObjectWithPublicationTaskAndMetadataNeedsTheMatchingPermission(
        array $data,
        string $missingPermission
    ): void {
        $object = $this->makeEmpty(Concrete::class);
        $saved = false;
        $checkedPermissions = [];

        $service = $this->createServiceForElement(
            $object,
            static function () use (&$saved): void {
                $saved = true;
            },
            static function (ElementInterface $element, UserInterface $user, string $permission) use (&$checkedPermissions, $missingPermission): void {
                $checkedPermissions[] = $permission;

                if ($permission === $missingPermission) {
                    throw new ForbiddenException(sprintf('You dont have %s permission', $permission));
                }
            }
        );

        try {
            $service->update(ElementTypes::TYPE_DATA_OBJECT, self::ELEMENT_ID, $data);
            $this->fail('Expected ForbiddenException was not thrown');
        } catch (ForbiddenException) {
            // expected - assertions happen after the try/catch
        }

        $this->assertContains($missingPermission, $checkedPermissions);
        $this->assertFalse($saved);

        // ... and the same request succeeds once that permission is granted
        $granted = [];
        $service = $this->createServiceForElement(
            $object,
            static function () use (&$saved): void {
                $saved = true;
            },
            static function (ElementInterface $element, UserInterface $user, string $permission) use (&$granted): void {
                $granted[] = $permission;
            }
        );

        $service->update(ElementTypes::TYPE_DATA_OBJECT, self::ELEMENT_ID, $data);

        $this->assertContains($missingPermission, $granted);
        $this->assertTrue($saved);
    }

    public static function publicationMetadataProvider(): array
    {
        return [
            'publish with key needs rename' => [['task' => 'publish', 'key' => 'renamed'], 'rename'],
            'unpublish with key needs rename' => [['task' => 'unpublish', 'key' => 'renamed'], 'rename'],
            'publish with parentId needs settings' => [['task' => 'publish', 'parentId' => 5], 'settings'],
            'publish with parentId needs create on the new parent' => [['task' => 'publish', 'parentId' => 5], 'create'],
            'publish with locked needs settings' => [['task' => 'publish', 'locked' => 'self'], 'settings'],
            'unpublish with unlock needs settings' => [['task' => 'unpublish', 'locked' => 'unlockPropagate'], 'settings'],
            'publish with properties needs properties' => [['task' => 'publish', 'properties' => []], 'properties'],
            'publish with editableData and key needs rename' => [
                ['task' => 'publish', 'editableData' => ['title' => 'x'], 'key' => 'renamed'],
                'rename',
            ],
            'publish with index needs save' => [['task' => 'publish', 'index' => 3], 'save'],
            'publish with children sort order needs save' => [['task' => 'publish', 'childrenSortOrder' => 'ASC'], 'save'],
            'publish with an unmapped key needs save' => [['task' => 'publish', 'somethingElse' => 1], 'save'],
        ];
    }

    /**
     * A user who may not publish at all is rejected before any data is applied, whatever travels with it.
     */
    public function testUpdateOfDataObjectWithPublicationTaskWithoutPublishPermissionAppliesNothing(): void
    {
        $object = $this->makeEmpty(Concrete::class);
        $saved = false;
        $applied = false;

        $service = $this->createServiceForElement(
            $object,
            static function () use (&$saved): void {
                $saved = true;
            },
            static function (ElementInterface $element, UserInterface $user, string $permission): void {
                throw new ForbiddenException(sprintf('You dont have %s permission', $permission));
            },
            $this->makeEmpty(DataObjectDataService::class, [
                'updateEditableData' => static function () use (&$applied): void {
                    $applied = true;
                },
            ])
        );

        try {
            $service->update(
                ElementTypes::TYPE_DATA_OBJECT,
                self::ELEMENT_ID,
                ['task' => 'publish', 'editableData' => ['title' => 'changed']]
            );
            $this->fail('Expected ForbiddenException was not thrown');
        } catch (ForbiddenException) {
            // expected - assertions happen after the try/catch
        }

        $this->assertFalse($applied);
        $this->assertFalse($saved);
    }

    /**
     * Untasked and non-publication writes to a data object still need the plain `save` permission.
     *
     * @dataProvider nonPublicationTaskProvider
     */
    public function testUpdateOfDataObjectWithoutPublicationTaskRequiresSavePermission(array $data): void
    {
        $object = $this->makeEmpty(Concrete::class);
        $saved = false;
        $checkedPermissions = [];

        $service = $this->createServiceForElement(
            $object,
            static function () use (&$saved): void {
                $saved = true;
            },
            static function (ElementInterface $element, UserInterface $user, string $permission) use (&$checkedPermissions): void {
                $checkedPermissions[] = $permission;

                throw new ForbiddenException(sprintf('You dont have %s permission', $permission));
            }
        );

        try {
            $service->update(ElementTypes::TYPE_DATA_OBJECT, self::ELEMENT_ID, $data);
            $this->fail('Expected ForbiddenException was not thrown');
        } catch (ForbiddenException) {
            // expected - assertions happen after the try/catch
        }

        $this->assertSame(['save'], $checkedPermissions);
        $this->assertFalse($saved);
    }

    public static function nonPublicationTaskProvider(): array
    {
        return [
            'no task, key' => [['key' => 'renamed']],
            'no task, editableData' => [['editableData' => ['title' => 'x']]],
            'save task' => [['task' => 'save', 'editableData' => ['title' => 'x']]],
            'autoSave task' => [['task' => 'autoSave']],
            'version task' => [['task' => 'version']],
        ];
    }

    /**
     * Legitimate writes to a folder must still succeed once the user actually holds `save`.
     */
    public function testUpdateOfDataObjectFolderSucceedsWithSavePermission(): void
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

        $service->update(ElementTypes::TYPE_DATA_OBJECT, self::ELEMENT_ID, ['properties' => []]);

        $this->assertSame(['save'], $checkedPermissions);
        $this->assertTrue($saved);
    }

    /**
     * An auto-save must not write the owners of a reverse relation; an explicit save (also Save draft) does.
     *
     * @dataProvider remoteChangeTasksProvider
     */
    public function testUpdateAppliesRemoteChangesOnlyForExplicitSaves(?string $task, bool $expectedApplied): void
    {
        $applied = null;
        $objectDataService = $this->makeEmpty(DataObjectDataService::class, [
            'updateEditableData' => static function (
                Concrete $element,
                array $editableData,
                UserInterface $user,
                bool $applyRemoteChanges = true
            ) use (&$applied): void {
                $applied = $applyRemoteChanges;
            },
        ]);
        $service = $this->createService(static fn () => null, $objectDataService, $this->makeEmpty(Concrete::class));

        $data = ['editableData' => ['owners' => [['id' => 7]]]];
        if ($task !== null) {
            $data['task'] = $task;
        }
        $service->update(ElementTypes::TYPE_OBJECT, self::ELEMENT_ID, $data);

        $this->assertSame($expectedApplied, $applied);
    }

    public static function remoteChangeTasksProvider(): array
    {
        return [
            'autoSave' => [ElementSaveTasks::AUTOSAVE->value, false],
            'unknown task' => ['draft', false],
            'version' => [ElementSaveTasks::VERSION->value, true],
            'save' => [ElementSaveTasks::SAVE->value, true],
            'publish' => [ElementSaveTasks::PUBLISH->value, true],
            'unpublish' => [ElementSaveTasks::UNPUBLISH->value, true],
            'no task' => [null, true],
        ];
    }

    private function createService(
        callable $onSave,
        ?DataObjectDataService $objectDataService = null,
        ?object $element = null
    ): UpdateService {
        $element ??= $this->element;

        return new UpdateService(
            adapterLoader: $this->makeEmpty(AdapterLoaderInterface::class, [
                'loadAdapters' => [],
            ]),
            objectDataService: $objectDataService ?? $this->makeEmpty(DataObjectDataService::class),
            documentDataService: $this->makeEmpty(DocumentDataService::class),
            securityService: $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $this->makeEmpty(UserInterface::class),
            ]),
            serviceResolver: $this->makeEmpty(ServiceResolverInterface::class, [
                'getElementById' => $element,
            ]),
            indexService: $this->makeEmpty(ElementIndexServiceInterface::class),
            elementSaveService: $this->makeEmpty(ElementSaveServiceInterface::class, [
                'save' => $onSave,
            ]),
            coauthorService: new CoauthorService($this->coauthorContext),
        );
    }

    private function createServiceForElement(
        ElementInterface $element,
        callable $onSave,
        callable $hasElementPermission,
        ?DataObjectDataService $objectDataService = null
    ): UpdateService {
        return new UpdateService(
            adapterLoader: $this->makeEmpty(AdapterLoaderInterface::class, [
                'loadAdapters' => [],
            ]),
            objectDataService: $objectDataService ?? $this->makeEmpty(DataObjectDataService::class),
            documentDataService: $this->makeEmpty(DocumentDataService::class),
            securityService: $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $this->makeEmpty(UserInterface::class),
                'hasElementPermission' => $hasElementPermission,
                'hasElementPermissions' => static function (
                    ElementInterface $element,
                    UserInterface $user,
                    array $permissions
                ) use ($hasElementPermission): void {
                    foreach ($permissions as $permission) {
                        $hasElementPermission($element, $user, $permission);
                    }
                },
            ]),
            serviceResolver: $this->makeEmpty(ServiceResolverInterface::class, [
                'getElementById' => $element,
            ]),
            indexService: $this->makeEmpty(ElementIndexServiceInterface::class),
            elementSaveService: $this->makeEmpty(ElementSaveServiceInterface::class, [
                'save' => $onSave,
            ]),
            coauthorService: new CoauthorService($this->coauthorContext),
        );
    }
}
