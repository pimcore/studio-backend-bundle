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

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Util\Constant\WorkflowUnsavedBehaviorTypes;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\AllowedTransition;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowActionServiceInterface;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Workflow\Transition;

/**
 * @internal
 */
final readonly class AllowedTransitionsHydrator implements AllowedTransitionsHydratorInterface
{
    public function __construct(
        private WorkflowActionServiceInterface $workflowActionService,
    ) {
    }

    /**
     * @return AllowedTransition[]
     */
    public function hydrate(array $allowedTransitions, ElementInterface $element): array
    {
        $hydrated = [];
        /** @var Transition $transition */
        foreach ($allowedTransitions as $transition) {
            $notes = $transition->getNotes();
            if (($element instanceof Concrete || $element instanceof Folder) && $notes) {
                $notes = $this->workflowActionService->enrichActionNotes($element, $notes);
            }
            $options = $transition->getOptions();

            $hydrated[] = new AllowedTransition(
                name: $transition->getName(),
                label: $transition->getLabel(),
                iconCls: $transition->getIconClass(),
                objectLayout: $transition->getObjectLayout(),
                unsavedChangesBehaviour:
                    $options['unsavedChangesBehaviour'] ?? WorkflowUnsavedBehaviorTypes::TYPE_WARN,
                notes: $notes ?? [],
            );
        }

        return $hydrated;
    }
}
