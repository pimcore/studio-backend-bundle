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
    schema: 'CreatePredefinedMetadata',
    title: 'Create Predefined Metadata',
    type: 'object'
)]
final readonly class CreatePredefinedMetadata
{
    public function __construct(
        #[Property(description: 'Name', type: 'string', example: 'New Definition')]
        private string $name = 'New Definition',
        #[Property(description: 'Type', type: 'string', example: 'input')]
        private string $type = 'input',
        #[Property(description: 'Description', type: 'string', example: 'A predefined metadata', nullable: true)]
        private ?string $description = null,
        #[Property(description: 'Target sub type', type: 'string', example: 'image', nullable: true)]
        private ?string $targetSubType = null,
        #[Property(description: 'Data', type: 'mixed', example: 'data', nullable: true)]
        private mixed $data = null,
        #[Property(description: 'Config', type: 'string', example: 'config', nullable: true)]
        private ?string $config = null,
        #[Property(description: 'Language', type: 'string', example: 'en', nullable: true)]
        private ?string $language = null,
        #[Property(description: 'Group', type: 'string', example: 'group', nullable: true)]
        private ?string $group = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDescription(): ?string
    {
        return $this->description;
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
