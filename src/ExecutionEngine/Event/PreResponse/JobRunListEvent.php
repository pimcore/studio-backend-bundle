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

namespace Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Schema\JobRun;

final class JobRunListEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.execution_engine.list_running_job_runs';

    public function __construct(
        private readonly JobRun $jobRun
    ) {
        parent::__construct($jobRun);
    }

    /**
     * Use this to get additional info out of the response object
     */
    public function getJobRun(): JobRun
    {
        return $this->jobRun;
    }
}
