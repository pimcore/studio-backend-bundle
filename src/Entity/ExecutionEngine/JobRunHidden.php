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

namespace Pimcore\Bundle\StudioBackendBundle\Entity\ExecutionEngine;

use Doctrine\ORM\Mapping as ORM;

/**
 * @internal
 */
#[ORM\Entity]
#[ORM\Table(name: JobRunHidden::TABLE_NAME)]
class JobRunHidden
{
    public const string TABLE_NAME = 'bundle_studio_execution_engine_hidden_job_run';

    #[ORM\Column(type: 'integer', nullable: false, options: ['unsigned' => true])]
    #[ORM\Id]
    private int $jobRunId;

    public function getJobRunId(): int
    {
        return $this->jobRunId;
    }

    public function setJobRunId(int $jobRunId): void
    {
        $this->jobRunId = $jobRunId;
    }
}
