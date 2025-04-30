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

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\Service;

use Pimcore\Bundle\StudioBackendBundle\Workflow\MappedParameter\WorkflowDetailsParameters;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\WorkflowDetails;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface WorkflowDetailsServiceInterface
{
    /**
     * @return WorkflowDetails[]
     */
    public function getWorkflowDetails(
        WorkflowDetailsParameters $parameters,
        UserInterface $user
    ): array;

    public function hasElementWorkflowsById(int $elementId, string $elementType, UserInterface $user): bool;

    public function hasElementWorkflows(ElementInterface $element): bool;
}
