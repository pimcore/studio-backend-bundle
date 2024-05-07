<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\Hydrator;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Request\WorkflowDetailsParameters;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\WorkflowDetails;
use Pimcore\Workflow\Manager;

/**
 * @internal
 */
final readonly class WorkflowHydratorService implements WorkflowHydratorServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private Manager $workflowManager,
        private ServiceResolverInterface $serviceResolver,
        private WorkflowDetailsHydratorInterface $workflowDetailsHydrator,
    ) {
    }

    /**
     * @return WorkflowDetails[]
     */
    public function hydrateWorkflowDetails(
        WorkflowDetailsParameters $parameters,
    ): array
    {
        $element = $this->getElement(
            $this->serviceResolver,
            $parameters->getElementType(),
            $parameters->getElementId(),
        );

        $details =  [];
        $elementWorkflows = $this->workflowManager->getAllWorkflowsForSubject($element);
        foreach ($elementWorkflows as $workflow) {
            $details[] = $this->workflowDetailsHydrator->hydrate(
                $element,
                $workflow
            );
        }

        return $details;
    }
}