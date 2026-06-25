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
use Throwable;
use function array_keys;

/**
 * @internal
 */
final readonly class WorkflowMetaService implements WorkflowMetaServiceInterface
{
    public function __construct(
        private Manager $workflowManager,
    ) {
    }

    public function getWorkflowNames(): array
    {
        return $this->workflowManager->getAllWorkflows();
    }

    public function getPlaces(string $workflowName): array
    {
        if ($workflowName === '') {
            return [];
        }

        try {
            $workflow = $this->workflowManager->getWorkflowByName($workflowName);
        } catch (Throwable) {
            return [];
        }

        if ($workflow === null) {
            return [];
        }

        return array_keys($workflow->getDefinition()->getPlaces());
    }
}
