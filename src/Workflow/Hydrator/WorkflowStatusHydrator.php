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

use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\WorkflowStatus;
use Pimcore\Workflow\Place\PlaceConfig;

/**
 * @internal
 */
final readonly class WorkflowStatusHydrator implements WorkflowStatusHydratorInterface
{
    public function hydrate(PlaceConfig $placeConfig, ?string $layoutId = null): WorkflowStatus
    {
        return new WorkflowStatus(
            $placeConfig->getColor(),
            $placeConfig->getColorInverted(),
            $placeConfig->getPlace(),
            $placeConfig->getLabel(),
            $layoutId,
            $placeConfig->isVisibleInHeader(),
        );
    }
}
