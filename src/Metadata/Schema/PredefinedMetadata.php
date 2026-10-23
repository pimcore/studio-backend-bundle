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

namespace Pimcore\Bundle\StudioBackendBundle\Metadata\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    title: 'PredefinedMetadata',
    required: ['id', 'name', 'type', 'creationDate', 'modificationDate', 'isWriteable'],
    type: 'object'
)]
final class PredefinedMetadata implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Id', type: 'string', example: '1')]
        private readonly string $id,
        #[Property(description: 'Name', type: 'string', example: 'custom_metadata')]
        private readonly string $name,
        #[Property(description: 'Description', type: 'string', example: 'A predefined metadata')]
        private readonly ?string $description,
        #[Property(description: 'Type', type: 'string', example: 'input')]
        private readonly string $type,
        #[Property(description: 'Target sub type', type: 'string', example: 'input')]
        private readonly ?string $targetSubType,
        #[Property(description: 'Data', type: 'mixed', example: 'data')]
        private readonly mixed $data,
        #[Property(description: 'Config', type: 'string', example: 'config')]
        private readonly ?string $config,
        #[Property(description: 'Language', type: 'string', example: 'en')]
        private readonly ?string $language,
        #[Property(description: 'Group', type: 'string', example: 'group')]
        private readonly ?string $group,
        #[Property(description: 'Creation Date', type: 'integer', example: 1634025600)]
        private readonly int $creationDate,
        #[Property(description: 'Modfication Date', type: 'integer', example: 1634025600)]
        private readonly int $modificationDate,
        #[Property(description: 'Writable', type: 'bool', example: false)]
        private readonly bool $isWriteable = false,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTargetSubType(): ?string
    {
        return $this->targetSubType;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getConfig(): ?string
    {
        return $this->config;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function getCreationDate(): int
    {
        return $this->creationDate;
    }

    public function getModificationDate(): int
    {
        return $this->modificationDate;
    }

    public function isWriteable(): bool
    {
        return $this->isWriteable;
    }
}
