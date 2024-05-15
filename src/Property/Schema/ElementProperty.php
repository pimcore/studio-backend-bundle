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

namespace Pimcore\Bundle\StudioBackendBundle\Property\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'DataProperty',
    type: 'object'
)]
final readonly class ElementProperty
{
    public function __construct(
        #[Property(description: 'key', type: 'string', example: 'key_of_the_property')]
        private string $key,
        #[Property(description: 'data', type: 'mixed', example: '123')]
        private mixed $data,
        #[Property(description: 'type', type: 'string', example: 'document')]
        private string $type,
        #[Property(description: 'inheritable', type: 'boolean', example: false)]
        private bool $inheritable,
        #[Property(description: 'inherited', type: 'boolean', example: false)]
        private bool $inherited,
        #[Property(description: 'config', type: 'string', example: 'comma,separated,values')]
        private ?string $config,
        #[Property(description: 'predefinedName', type: 'string', example: 'name of the predefined property')]
        private ?string $predefinedName,
        #[Property(description: 'description', type: 'string', example: 'Description of the predefined property')]
        private ?string $description,
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
