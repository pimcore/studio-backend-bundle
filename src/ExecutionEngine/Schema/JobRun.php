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

namespace Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'JobRun',
    title: 'JobRun',
    required: ['id', 'ownerId', 'state', 'executionContext', 'totalElements', 'creationDate', 'modificationDate'],
    type: 'object'
)]
final class JobRun implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'ID', type: 'int', example: 1)]
        private readonly int $id,
        #[Property(description: 'Owner ID', type: 'int', example: 123)]
        private readonly ?int $ownerId,
        #[Property(description: 'State', type: 'string', example: JobRunStates::RUNNING->value)]
        private readonly string $state,
        #[Property(description: 'Execution context', type: 'string', example: Config::CONTEXT_STOP_ON_ERROR->value)]
        private readonly string $executionContext,
        #[Property(description: 'Total elements', type: 'integer', example: 0)]
        private readonly int $totalElements,
        #[Property(description: 'Current Message og the last Event', type: 'string', example: 'Message')]
        private readonly string $currentMessage,
        #[Property(description: 'Current Step of a running Job', type: 'integer', example: 0)]
        private readonly ?int $currentStep = null,
        #[Property(description: 'Number of total Steps of a running Job', type: 'integer', example: 0)]
        private readonly ?int $totalSteps = null,
        #[Property(description: 'Creation date', type: 'integer', example: null)]
        private readonly ?int $creationDate = null,
        #[Property(description: 'Modification date', type: 'integer', example: null)]
        private readonly ?int $modificationDate = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOwnerId(): ?int
    {
        return $this->ownerId;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getExecutionContext(): string
    {
        return $this->executionContext;
    }

    public function getTotalElements(): int
    {
        return $this->totalElements;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function getCurrentStep(): ?int
    {
        return $this->currentStep;
    }

    public function getCurrentMessage(): array
    {
        return json_decode($this->currentMessage, true);
    }

    public function getTotalSteps(): ?int
    {
        return $this->totalSteps;
    }
}
