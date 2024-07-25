<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Schema\ExecutionEngine;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'SSEHandlerProgress',
    required: [
        'progress',
        'currentStep',
        'totalSteps',
        'jobStepName',
        'jobName',
        'jobRunId',
        'user',
    ],
    type: 'object'
)]
final readonly class Progress
{
    public function __construct(
        #[Property(description: 'progress', type: 'integer', example: 69)]
        private int $progress,
        #[Property(description: 'currentStep', type: 'integer', example: 1)]
        private int $currentStep,
        #[Property(description: 'totalSteps', type: 'integer', example: 3)]
        private int $totalSteps,
        #[Property(description: 'jobStepName', type: 'string', example: 'Job Step Name')]
        private string $jobStepName,
        #[Property(description: 'jobName', type: 'string', example: 'Job Name')]
        private string $jobName,
        #[Property(description: 'jobRunId', type: 'integer', example: 73)]
        private int $jobRunId,
        #[Property(description: 'user', type: 'integer', example: 2)]
        private int $user,
    ) {
    }

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function getCurrentStep(): int
    {
        return $this->currentStep;
    }

    public function getTotalSteps(): int
    {
        return $this->totalSteps;
    }

    public function getJobStepName(): string
    {
        return $this->jobStepName;
    }

    public function getJobName(): string
    {
        return $this->jobName;
    }

    public function getJobRunId(): int
    {
        return $this->jobRunId;
    }

    public function getUser(): int
    {
        return $this->user;
    }
}
