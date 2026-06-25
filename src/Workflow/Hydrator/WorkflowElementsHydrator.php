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

use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\WorkflowElement;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 */
final readonly class WorkflowElementsHydrator implements WorkflowElementsHydratorInterface
{
    public function hydrate(
        ElementInterface $element,
        array $row,
        string $workflowName,
        string $stateName,
        string $stateLabel,
        string $stateColor,
    ): WorkflowElement {
        $ctype = (string) $row['ctype'];

        return new WorkflowElement(
            (int) $row['cid'],
            $ctype === ElementTypes::TYPE_OBJECT ? ElementTypes::TYPE_DATA_OBJECT : $ctype,
            $element->getFullPath(),
            $element->getKey(),
            $workflowName,
            $stateName,
            $stateLabel,
            $stateColor,
            $element->getModificationDate() ?? (int) $row['modificationDate'],
        );
    }
}
