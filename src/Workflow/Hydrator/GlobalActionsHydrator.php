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

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\GlobalAction;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowActionServiceInterface;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Workflow\GlobalAction as PimcoreGlobalAction;

/**
 * @internal
 */
final readonly class GlobalActionsHydrator implements GlobalActionsHydratorInterface
{
    public function __construct(
        private WorkflowActionServiceInterface $workflowActionService,
    ) {
    }

    /**
     * @return GlobalAction[]
     */
    public function hydrate(array $globalActionsArray, ElementInterface $element): array
    {
        $hydrated = [];
        /** @var PimcoreGlobalAction $action */
        foreach ($globalActionsArray as $action) {
            $notes = $action->getNotes();
            if (($element instanceof Concrete || $element instanceof Folder) && $notes) {
                $notes = $this->workflowActionService->enrichActionNotes($action, $element, $notes);
            }
            $hydrated[] = new GlobalAction(
                name: $action->getName(),
                label: $action->getLabel(),
                iconCls: $action->getIconClass(),
                objectLayout: $action->getObjectLayout(),
                notes: $notes ?? [],
            );
        }

        return $hydrated;
    }
}
