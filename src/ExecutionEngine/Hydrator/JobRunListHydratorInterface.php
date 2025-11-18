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

namespace Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Hydrator;

use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun as JobRunEntity;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Schema\JobRun;

/**
 * @internal
 */
interface JobRunListHydratorInterface
{
    public function hydrate(JobRunEntity $jobRun, bool $forceReload = false): JobRun;
}
