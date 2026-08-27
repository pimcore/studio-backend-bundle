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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\AssetPermissions;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObjectPermissions;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\WorkflowPermissionMerger;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Workflow\Manager;
use Pimcore\Workflow\Place\PlaceConfig;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\WorkflowInterface;
use function sprintf;

/**
 * @internal
 */
final class WorkflowPermissionMergerTest extends Unit
{
    /**
     * A permission that the user is allowed but the workflow denies must become denied,
     * and the workflow state must be evaluated only once per element.
     */
    public function testWorkflowDenialRestrictsUserPermissions(): void
    {
        $merger = new WorkflowPermissionMerger(
            $this->createManagerDenying(['delete' => false, 'save' => false])
        );

        $merged = $merger->mergeWorkflowPermissions(
            new DataObjectPermissions(
                save: true,
                unpublish: true,
                list: true,
                view: true,
                publish: true,
                delete: true,
                rename: true,
                create: true,
                settings: true,
                versions: true,
                properties: true,
            ),
            $this->createMock(ElementInterface::class)
        );

        $this->assertInstanceOf(DataObjectPermissions::class, $merged);
        // denied by workflow
        $this->assertFalse($merged->isDelete());
        $this->assertFalse($merged->isSave());
        // not denied by workflow -> kept
        $this->assertTrue($merged->isPublish());
        $this->assertTrue($merged->isView());
        $this->assertTrue($merged->isUnpublish());
        $this->assertTrue($merged->isRename());
    }

    /**
     * A permission already denied for the user stays denied even if the workflow allows it.
     */
    public function testUserDenialIsPreservedWhenWorkflowAllows(): void
    {
        $merger = new WorkflowPermissionMerger(
            $this->createManagerDenying([])
        );

        $merged = $merger->mergeWorkflowPermissions(
            new AssetPermissions(
                list: true,
                view: true,
                publish: true,
                delete: false,
                rename: true,
                create: true,
                settings: true,
                versions: true,
                properties: true,
            ),
            $this->createMock(ElementInterface::class)
        );

        $this->assertInstanceOf(AssetPermissions::class, $merged);
        $this->assertFalse($merged->isDelete());
        $this->assertTrue($merged->isView());
    }

    /**
     * Drift detector: the merger mirrors the private core aggregation behind
     * Manager::isDeniedInWorkflow(). This test runs the REAL isDeniedInWorkflow() (only the
     * fixture collaborators getAllWorkflows/getWorkflowIfExists/getOrderedPlaceConfigs are
     * stubbed) against the merger on identical workflow data and asserts both agree on every
     * permission flag. If core changes its aggregation semantics, this fails instead of the
     * grid silently disagreeing with enforcement.
     */
    public function testMergerAgreesWithCoreIsDeniedInWorkflowForEveryFlag(): void
    {
        $placePermissions = [
            'save' => false,
            'unpublish' => false,
            'delete' => false,
            'rename' => false,
            'properties' => false,
            'view' => true,
            'publish' => true,
            'settings' => true,
            'versions' => true,
        ];

        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->method('getMarking')->willReturn(new Marking(['test_place' => 1]));

        $placeConfig = $this->createMock(PlaceConfig::class);
        $placeConfig->method('getPermissions')->willReturn($placePermissions);
        $placeConfig->method('getUserPermissions')->willReturn($placePermissions);

        $manager = $this->getMockBuilder(Manager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAllWorkflows', 'getWorkflowIfExists', 'getOrderedPlaceConfigs'])
            ->getMock();
        $manager->method('getAllWorkflows')->willReturn(['test_workflow']);
        $manager->method('getWorkflowIfExists')->willReturn($workflow);
        $manager->method('getOrderedPlaceConfigs')->willReturn([$placeConfig]);

        $element = $this->createMock(ElementInterface::class);

        $merged = (new WorkflowPermissionMerger($manager))->mergeWorkflowPermissions(
            new DataObjectPermissions(
                save: true,
                unpublish: true,
                list: true,
                view: true,
                publish: true,
                delete: true,
                rename: true,
                create: true,
                settings: true,
                versions: true,
                properties: true,
            ),
            $element
        );

        $this->assertInstanceOf(DataObjectPermissions::class, $merged);

        $flagGetters = [
            'save' => 'isSave',
            'unpublish' => 'isUnpublish',
            'view' => 'isView',
            'publish' => 'isPublish',
            'delete' => 'isDelete',
            'rename' => 'isRename',
            'settings' => 'isSettings',
            'versions' => 'isVersions',
            'properties' => 'isProperties',
        ];

        foreach ($flagGetters as $flag => $getter) {
            $this->assertSame(
                !$manager->isDeniedInWorkflow($element, $flag),
                $merged->$getter(),
                sprintf('Merger disagrees with Manager::isDeniedInWorkflow() for the "%s" flag', $flag)
            );
        }
    }

    /**
     * Builds a Manager whose single workflow place returns the given permission map. The map is
     * merged exactly once per element (asserted via the expectation on getAllWorkflows()).
     *
     * @param array<string, bool> $placePermissions
     */
    private function createManagerDenying(array $placePermissions): Manager
    {
        $manager = $this->createMock(Manager::class);
        $manager->expects($this->once())->method('getAllWorkflows')->willReturn(
            $placePermissions === [] ? [] : ['test_workflow']
        );

        if ($placePermissions === []) {
            return $manager;
        }

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
