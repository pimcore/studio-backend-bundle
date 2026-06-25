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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Workflow\Service;

use Codeception\Test\Unit;
use LogicException;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowMetaService;
use Pimcore\Workflow\Manager;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @internal
 */
final class WorkflowMetaServiceTest extends Unit
{
    public function testGetWorkflowNamesReturnsAllRegisteredWorkflows(): void
    {
        $service = $this->createService(
            $this->make(Manager::class, [
                'getAllWorkflows' => ['product_workflow', 'asset_workflow'],
            ])
        );

        $this->assertSame(['product_workflow', 'asset_workflow'], $service->getWorkflowNames());
    }

    public function testGetPlacesReturnsDefinitionPlaceNames(): void
    {
        $workflow = $this->makeEmpty(WorkflowInterface::class, [
            'getDefinition' => new Definition(['open', 'in_review', 'closed'], []),
        ]);
        $service = $this->createService(
            $this->make(Manager::class, [
                'getWorkflowByName' => $workflow,
            ])
        );

        $this->assertSame(['open', 'in_review', 'closed'], $service->getPlaces('product_workflow'));
    }

    public function testGetPlacesReturnsEmptyArrayForBlankWorkflowName(): void
    {
        $service = $this->createService();

        $this->assertSame([], $service->getPlaces(''));
    }

    public function testGetPlacesReturnsEmptyArrayWhenWorkflowDoesNotExist(): void
    {
        $service = $this->createService(
            $this->make(Manager::class, [
                'getWorkflowByName' => null,
            ])
        );

        $this->assertSame([], $service->getPlaces('unknown_workflow'));
    }

    public function testGetPlacesReturnsEmptyArrayWhenManagerThrows(): void
    {
        $service = $this->createService(
            $this->make(Manager::class, [
                'getWorkflowByName' => static function (): never {
                    throw new LogicException('workflow unknown_workflow not found');
                },
            ])
        );

        $this->assertSame([], $service->getPlaces('unknown_workflow'));
    }

    private function createService(?Manager $workflowManager = null): WorkflowMetaService
    {
        return new WorkflowMetaService(
            $workflowManager ?? $this->makeEmpty(Manager::class)
        );
    }
}
