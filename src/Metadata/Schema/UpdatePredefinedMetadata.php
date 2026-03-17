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

/**
 * @internal
 */
#[Schema(
    schema: 'UpdatePredefinedMetadata',
    title: 'Update Predefined Metadata',
    required: ['name', 'description', 'type', 'targetSubType', 'data', 'config', 'language', 'group'],
    type: 'object'
)]
final readonly class UpdatePredefinedMetadata
{
    public function __construct(
        #[Property(description: 'Name', type: 'string', example: 'custom_metadata')]
        private string $name,
        #[Property(description: 'Description', type: 'string', example: 'A predefined metadata')]
        private ?string $description,
        #[Property(description: 'Type', type: 'string', example: 'input')]
        private string $type,
        #[Property(description: 'Target sub type', type: 'string', example: 'image')]
        private ?string $targetSubType,
        #[Property(description: 'Data', type: 'mixed', example: 'data')]
        private mixed $data,
        #[Property(description: 'Config', type: 'string', example: 'config')]
        private ?string $config,
        #[Property(description: 'Language', type: 'string', example: 'en')]
        private ?string $language,
        #[Property(description: 'Group', type: 'string', example: 'group')]
        private ?string $group,
    ) {
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
}
