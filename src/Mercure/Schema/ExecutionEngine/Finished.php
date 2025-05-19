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

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Schema\ExecutionEngine;

use OpenApi\Attributes\Items;
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
        'ownerId',
        'state',
        'messages',
        'notifyAll',
    ],
    type: 'object'
)]
final readonly class Finished
{
    public function __construct(
        #[Property(description: 'jobRunId', type: 'integer', example: 73)]
        private int $jobRunId,
        #[Property(description: 'jobRunName', type: 'string', example: 'my-job-name')]
        private string $jobRunName,
        #[Property(description: 'ownerId', type: 'integer', example: 13)]
        private int $ownerId,
        #[Property(description: 'status', type: 'string', example: JobRunStates::FINISHED->value)]
        private string $status,
        #[Property(
            description: 'messages',
            type: 'array',
            items: new Items(type: 'string'),
            example: ['Something went wrong']
        )]
        private array $messages = [],
        #[Property(description: 'notifyAll', type: 'boolean', example: false)]
        private bool $notifyAll = false,
    ) {
    }

    public function getJobRunId(): int
    {
        return $this->jobRunId;
    }

    public function getJobRunName(): string
    {
        return $this->jobRunName;
    }

    public function getOwnerId(): int
    {
        return $this->ownerId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function isNotifyAll(): bool
    {
        return $this->notifyAll;
    }
}
