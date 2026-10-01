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
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\VersionDraftElementResolver;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Hydrator\WorkflowDetailsHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Workflow\MappedParameter\WorkflowDetailsParameters;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowDetailsService;
use Pimcore\Model\UserInterface;
use Pimcore\Workflow\Manager;
use Symfony\Component\EventDispatcher\EventDispatcher;

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
        $this->expectExceptionMessage('Asset with ID: 1 not found');
        $this->expectException(NotFoundException::class);
        $workflowDetailsService->getWorkflowDetails(
            $parameters,
            $this->makeEmpty(UserInterface::class)
        );
    }

    private function getWorkflowDetailsService(): WorkflowDetailsService
    {
        return new WorkflowDetailsService(
            $this->makeEmpty(EventDispatcher::class),
            $this->makeEmpty(Manager::class),
            $this->makeEmpty(SecurityServiceInterface::class),
            $this->makeEmpty(ServiceResolverInterface::class),
            $this->makeEmpty(WorkflowDetailsHydratorInterface::class),
            new VersionDraftElementResolver()
        );
    }
}
