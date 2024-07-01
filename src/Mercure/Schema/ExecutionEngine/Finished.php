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
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;

/**
 * @internal
 */
#[Schema(
    title: 'SSEJobRunFinished',
    required: [
        'jobRunId',
        'jobRunName',
        'state',
    ],
    type: 'object'
)]
final readonly class Finished
{
    public function __construct(
        #[Property(description: 'jobRunId', type: 'integer', example: 73)]
        private int $jobRunId,
        #[Property(description: 'jobRunName', type: 'string', example: 'my-job-name')]
        private string $jobName,
        #[Property(description: 'status', type: 'string', example: JobRunStates::FINISHED->value)]
        private string $status,
        #[Property(description: 'messages', type: 'list', example: ['Something went wrong'])]
        private array $messages = [],
    ) {
    }

    public function getJobRunId(): int
    {
        return $this->jobRunId;
    }

    public function getJobName(): string
    {
        return $this->jobName;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }
}
