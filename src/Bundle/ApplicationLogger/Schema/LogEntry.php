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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'BundleApplicationLoggerLogEntry',
    title: 'Bundle Application Logger Log Entry',
    required: [
        'id', 'priority', 'date', 'pid', 'message',
        'fileObject', 'relatedObjectId', 'relatedObjectType', 'component', 'source',
    ],
    type: 'object'
)]
final class LogEntry implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'ID', type: 'integer', example: 1)]
        private readonly int $id,
        #[Property(description: 'Log priority level', type: 'integer', example: 2)]
        private readonly int $priority,
        #[Property(description: 'Date', type: 'string', example: '2023-10-01T12:00:00+00:00')]
        private readonly ?string $date = null,
        #[Property(description: 'PID', type: 'integer', example: 22)]
        private readonly ?int $pid = null,
        #[Property(description: 'Message', type: 'string', example: 'Some Log Message')]
        private readonly ?string $message = null,
        #[Property(description: 'File object path', type: 'string', example: 'path/to/file.txt')]
        private readonly ?string $fileObject = null,
        #[Property(description: 'ID of related object', type: 'integer', example: 1)]
        private readonly ?int $relatedObjectId = null,
        #[Property(description: 'Type of related object', type: 'string', example: 'asset')]
        private readonly ?string $relatedObjectType = null,
        #[Property(description: 'Component', type: 'string', example: 'SomeComponent::Class')]
        private readonly ?string $component = null,
        #[Property(description: 'Source', type: 'string', example: 'Pimcore\Bundle')]
        private readonly ?string $source = null,

    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getFileObject(): ?string
    {
        return $this->fileObject;
    }

    public function getRelatedObjectId(): ?int
    {
        return $this->relatedObjectId;
    }

    public function getRelatedObjectType(): ?string
    {
        return $this->relatedObjectType;
    }

    public function getComponent(): ?string
    {
        return $this->component;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }
}
