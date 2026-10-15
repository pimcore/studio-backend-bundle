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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataObject\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\DataObjectQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\DataObjectSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObjectDetail;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObjectPermissions;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Type\DataObjectFolder;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataObjectService;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementSaveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\WorkflowPermissionMerger;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementIconTypes;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\FactoryInterface;
use Pimcore\Model\UserInterface;
use Pimcore\Workflow\Manager;
use Pimcore\Workflow\Place\PlaceConfig;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @internal
 */
final class DataObjectServiceTest extends Unit
{
    private const int OBJECT_ID = 5;

    /**
     * The editor derives its "Save & Publish" action from the permissions of the detail response.
     * A publish that the current workflow place denies must therefore not be reported as allowed,
     * while everything the place does not control stays as the user's permissions say.
     */
    public function testDetailPermissionsAreRestrictedByTheWorkflowPlace(): void
    {
        $detail = $this->createDetail(hasWorkflowWithPermissions: true);
        $service = $this->createService(
            $this->createSearchService($detail),
            $this->createManagerDenying(['publish' => false])
        );

        $result = $service->getDataObject(self::OBJECT_ID, false);

        $this->assertSame($detail, $result);
        $this->assertFalse($result->getPermissions()->isPublish());
        $this->assertTrue($result->getPermissions()->isSave());
        $this->assertTrue($result->getPermissions()->isUnpublish());
        $this->assertTrue($result->getPermissions()->isView());
        $this->assertSame('en,de', $result->getPermissions()->getLocalizedEdit());
    }

    /**
     * A place that denies saving must hide the ordinary save action as well, not only the publish one.
     */
    public function testDetailSaveDeniedByTheWorkflowPlaceIsReported(): void
    {
        $service = $this->createService(
            $this->createSearchService($this->createDetail(hasWorkflowWithPermissions: true)),
            $this->createManagerDenying(['save' => false])
        );

        $permissions = $service->getDataObject(self::OBJECT_ID, false)->getPermissions();

        $this->assertFalse($permissions->isSave());
        $this->assertTrue($permissions->isPublish());
    }

    /**
     * A workflow place that allows publishing must not grant more than the user has.
     */
    public function testWorkflowDoesNotGrantPermissionsTheUserDoesNotHave(): void
    {
        $service = $this->createService(
            $this->createSearchService($this->createDetail(hasWorkflowWithPermissions: true, publish: false)),
            $this->createManagerDenying(['publish' => true])
        );

        $this->assertFalse($service->getDataObject(self::OBJECT_ID, false)->getPermissions()->isPublish());
    }

    /**
     * Without a workflow that carries place permissions nothing is evaluated: the element is not
     * loaded and the permissions of the index stay untouched.
     */
    public function testPermissionsAreKeptWhenNoWorkflowWithPermissionsApplies(): void
    {
        $detail = $this->createDetail(hasWorkflowWithPermissions: false);
        $manager = $this->createMock(Manager::class);
        $manager->expects($this->never())->method('getAllWorkflows');

        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver->expects($this->never())->method('getElementById');

        $service = $this->createService($this->createSearchService($detail), $manager, $serviceResolver);

        $this->assertTrue($service->getDataObject(self::OBJECT_ID, false)->getPermissions()->isPublish());
    }

    /**
     * The by-user detail lookup (used outside the current request user) applies the same restriction.
     */
    public function testDetailForUserPermissionsAreRestrictedByTheWorkflowPlace(): void
    {
        $service = $this->createService(
            $this->createSearchService($this->createDetail(hasWorkflowWithPermissions: true)),
            $this->createManagerDenying(['publish' => false])
        );

        $result = $service->getDataObjectForUser(self::OBJECT_ID, $this->createMock(UserInterface::class));

        $this->assertFalse($result->getPermissions()->isPublish());
        $this->assertTrue($result->getPermissions()->isSave());
    }

    /**
     * Folders go through the same detail path and are restricted the same way.
     */
    public function testFolderPermissionsAreRestrictedByTheWorkflowPlace(): void
    {
        $service = $this->createService(
            $this->createSearchService($this->createFolder(hasWorkflowWithPermissions: true)),
            $this->createManagerDenying(['publish' => false])
        );

        $this->assertFalse($service->getDataObject(self::OBJECT_ID, false)->getPermissions()->isPublish());
    }

    private function createService(
        DataObjectSearchServiceInterface $searchService,
        Manager $manager,
        ?ServiceResolverInterface $serviceResolver = null,
    ): DataObjectService {
        if ($serviceResolver === null) {
            $serviceResolver = $this->createMock(ServiceResolverInterface::class);
            $serviceResolver->method('getElementById')->willReturn($this->createMock(Concrete::class));
        }

        $securityService = $this->createMock(SecurityServiceInterface::class);
        $securityService->method('getCurrentUser')->willReturn($this->createMock(UserInterface::class));

        return new DataObjectService(
            $this->createMock(ClassDefinitionResolverInterface::class),
            $this->createMock(DataServiceInterface::class),
            $this->createMock(DataObjectQueryProviderInterface::class),
            $searchService,
            $this->createMock(DataObjectServiceResolverInterface::class),
            $this->createMock(FactoryInterface::class),
            $this->createMock(FilterServiceProviderInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            $securityService,
            $serviceResolver,
            $this->createMock(ElementSaveServiceInterface::class),
            new WorkflowPermissionMerger($manager)
        );
    }

    private function createSearchService(DataObjectDetail|DataObjectFolder $dataObject): DataObjectSearchServiceInterface
    {
        $searchService = $this->createMock(DataObjectSearchServiceInterface::class);
        $searchService->method('getDataObjectById')->willReturn($dataObject);

        return $searchService;
    }

    private function createDetail(bool $hasWorkflowWithPermissions, bool $publish = true): DataObjectDetail
    {
        return new DataObjectDetail(
            'object',
            'Product',
            'object',
            true,
            false,
            $hasWorkflowWithPermissions,
            '/object',
            $this->createPermissions($publish),
            0,
            'key',
            'ASC',
            false,
            self::OBJECT_ID,
            1,
            '/',
            new ElementIcon(ElementIconTypes::NAME->value, 'class'),
            1,
            1,
            null,
            false,
            1,
            1
        );
    }

    private function createFolder(bool $hasWorkflowWithPermissions): DataObjectFolder
    {
        return new DataObjectFolder(
            'folder',
            'folder',
            'folder',
            true,
            true,
            $hasWorkflowWithPermissions,
            '/folder',
            $this->createPermissions(true),
            0,
            'key',
            'ASC',
            false,
            self::OBJECT_ID,
            1,
            '/',
            new ElementIcon(ElementIconTypes::NAME->value, 'folder'),
            1,
            1,
            null,
            false,
            1,
            1
        );
    }

    private function createPermissions(bool $publish): DataObjectPermissions
    {
        return new DataObjectPermissions(
            save: true,
            unpublish: true,
            localizedEdit: 'en,de',
            localizedView: 'en,de',
            list: true,
            view: true,
            publish: $publish,
            delete: true,
            rename: true,
            create: true,
            settings: true,
            versions: true,
            properties: true,
        );
    }

    /**
     * Builds a Manager whose single workflow place returns the given permission map.
     *
     * @param array<string, bool> $placePermissions
     */
    private function createManagerDenying(array $placePermissions): Manager
    {
        $manager = $this->createMock(Manager::class);
        $manager->method('getAllWorkflows')->willReturn(['test_workflow']);

        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->method('getMarking')->willReturn(new Marking(['test_place' => 1]));

        $placeConfig = $this->createMock(PlaceConfig::class);
        $placeConfig->method('getPermissions')->willReturn($placePermissions);
        $placeConfig->method('getUserPermissions')->willReturn($placePermissions);

        $manager->method('getWorkflowIfExists')->willReturn($workflow);
        $manager->method('getOrderedPlaceConfigs')->willReturn([$placeConfig]);

        return $manager;
    }
}
