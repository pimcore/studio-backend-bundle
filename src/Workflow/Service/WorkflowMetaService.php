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

use Pimcore\Workflow\Manager;
use function array_keys;
use function in_array;

/**
 * @internal
 */
final readonly class WorkflowMetaService implements WorkflowMetaServiceInterface
{
    public function __construct(
        private Manager $workflowManager,
    ) {
    }

    /**
     * @return string[]
     */
    public function getWorkflowNames(): array
    {
        return $this->workflowManager->getAllWorkflows();
    }

    /**
     * @return string[]
     */
    public function getPlaces(string $workflowName): array
    {
        if ($workflowName === '' || !in_array($workflowName, $this->workflowManager->getAllWorkflows(), true)) {
            return [];
        }

        $workflow = $this->workflowManager->getWorkflowByName($workflowName);
        if ($workflow === null) {
            return [];
        }

        return array_keys($workflow->getDefinition()->getPlaces());
    }
}
