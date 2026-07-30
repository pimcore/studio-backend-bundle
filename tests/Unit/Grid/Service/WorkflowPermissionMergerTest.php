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

/**
 * @internal
 */
final class WorkflowPermissionMergerTest extends Unit
{
    /**
     * A permission that the user is allowed but the workflow denies must become denied.
     */
    public function testWorkflowDenialRestrictsUserPermissions(): void
    {
        $merger = new WorkflowPermissionMerger(
            $this->createManagerDenying(['delete', 'save'])
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
     * @param array<int, string> $deniedPermissions
     */
    private function createManagerDenying(array $deniedPermissions): Manager
    {
        $manager = $this->createMock(Manager::class);
        $manager->method('isDeniedInWorkflow')
            ->willReturnCallback(
                static fn (ElementInterface $element, string $permissionType): bool
                    => in_array($permissionType, $deniedPermissions, true)
            );

        return $manager;
    }
}
