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

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\Util\Trait;

use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\WorkflowDetails;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\WorkflowStatus;

/**
 * @internal
 */
trait WorkflowLayoutTrait
{
    /**
     * @param WorkflowStatus[]|WorkflowDetails[] $data
     */
    private function getLastLayoutId(array $data): ?string
    {
        $workflowLayoutId = null;

        foreach ($data as $item) {
            $workflowLayoutId = $item->getLayoutId();
        }

        return $workflowLayoutId;
    }
}
