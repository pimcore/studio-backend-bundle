<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Workflow\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Hydrator\AllowedTransitionsHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Hydrator\GlobalActionsHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Workflow\MappedParameter\WorkflowDetailsParameters;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowActionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowDetailsService;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowGraphServiceInterface;
use Pimcore\Model\UserInterface;
use Pimcore\Workflow\Manager;

/**
 * @internal
 */
final class WorkflowDetailsServiceTest extends Unit
{
    public function testHydrateWorkflowDetails(): void
    {
        $parameters = new WorkflowDetailsParameters(
            elementId: 1,
            elementType: 'asset',
        );
        $workflowDetailsService = $this->getWorkflowDetailsService();
        $this->expectExceptionMessage('Element with ID 1 not found');
        $this->expectException(ElementNotFoundException::class);
        $workflowDetailsService->getWorkflowDetails(
            $parameters,
            $this->makeEmpty(UserInterface::class)
        );
    }

    private function getWorkflowDetailsService(): WorkflowDetailsService
    {
        return new WorkflowDetailsService(
            $this->makeEmpty(AllowedTransitionsHydratorInterface::class),
            $this->makeEmpty(GlobalActionsHydratorInterface::class),
            $this->makeEmpty(Manager::class),
            $this->makeEmpty(SecurityServiceInterface::class),
            $this->makeEmpty(ServiceResolverInterface::class),
            $this->makeEmpty(WorkflowActionServiceInterface::class),
            $this->makeEmpty(WorkflowGraphServiceInterface::class)
        );
    }
}
