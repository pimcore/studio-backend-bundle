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

namespace Pimcore\Bundle\StudioBackendBundle\Property\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    title: 'DataProperty',
    required: ['key', 'data', 'type', 'inheritable', 'inherited'],
    type: 'object'
)]
final class ElementProperty implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'key', type: 'string', example: 'key_of_the_property')]
        private readonly string $key,
        #[Property(description: 'data', type: 'mixed', example: '123')]
        private readonly mixed $data,
        #[Property(description: 'type', type: 'string', example: 'document')]
        private readonly string $type,
        #[Property(description: 'inheritable', type: 'boolean', example: false)]
        private readonly bool $inheritable,
        #[Property(description: 'inherited', type: 'boolean', example: false)]
        private readonly bool $inherited,
        #[Property(description: 'config', type: 'string', example: 'comma,separated,values')]
        private readonly ?string $config,
        #[Property(description: 'predefinedName', type: 'string', example: 'name of the predefined property')]
        private readonly ?string $predefinedName,
        #[Property(description: 'description', type: 'string', example: 'Description of the predefined property')]
        private readonly ?string $description,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isInheritable(): bool
    {
        return $this->inheritable;
    }

    public function isInherited(): bool
    {
        return $this->inherited;
    }

    public function getConfig(): ?string
    {
        return $this->config;
    }

    public function getPredefinedName(): ?string
    {
        return $this->predefinedName;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
