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
        private string $id,
        #[Property(description: 'Name', type: 'string', example: 'custom_metadata')]
        private string $name,
        #[Property(description: 'Description', type: 'string', example: 'A predefined metadata')]
        private ?string $description,
        #[Property(description: 'Type', type: 'string', example: 'input')]
        private string $type,
        #[Property(description: 'Target sub type', type: 'string', example: 'input')]
        private ?string $targetSubType,
        #[Property(description: 'Data', type: 'mixed', example: 'data')]
        private mixed $data,
        #[Property(description: 'Config', type: 'string', example: 'config')]
        private ?string $config,
        #[Property(description: 'Language', type: 'string', example: 'en')]
        private ?string $language,
        #[Property(description: 'Group', type: 'string', example: 'group')]
        private ?string $group,
        #[Property(description: 'Creation Date', type: 'integer', example: 1634025600)]
        private int $creationDate,
        #[Property(description: 'Modfication Date', type: 'integer', example: 1634025600)]
        private int $modificationDate,
        #[Property(description: 'Writable', type: 'bool', example: false)]
        private bool $isWriteable = false,
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
