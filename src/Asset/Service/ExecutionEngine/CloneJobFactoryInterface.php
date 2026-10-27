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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine;

use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Model\CloneDescendants;

/**
 * @internal
 */
interface CloneJobFactoryInterface
{
    /**
     * Creates the job that clones the descendants of an asset folder into its already cloned copy.
     * Returns null when there is nothing to clone.
     */
    public function create(CloneDescendants $descendants, int $originalParentId, int $parentId): ?Job;
}
