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
    title: 'PredefinedProperty',
    type: 'object'
)]
final readonly class PredefinedProperty
{
    public function __construct(
        #[Property(description: 'id', type: 'string', example: 'alpha-numerical-value')]
        private string $id,
        #[Property(description: 'name', type: 'string', example: 'Mister Proper')]
        private string $name,
        #[Property(description: 'description', type: 'string', example: 'Detailed description of the property')]
        private ?string $description,
        #[Property(description: 'key', type: 'string', example: 'Key for referencing')]
        private string $key,
        #[Property(description: 'type', type: 'string', example: 'text')]
        private string $type,
        #[Property(description: 'data', type: 'string', example: 'test')]
        private ?string $data,
        #[Property(description: 'config', type: 'string', example: 'comma,separated,values')]
        private ?string $config,
        #[Property(description: 'ctype', type: 'string', example: 'document')]
        private string $ctype,
        #[Property(description: 'inheritable', type: 'boolean', example: false)]
        private bool $inheritable,
        #[Property(description: 'Creation date', type: 'integer', example: 221846400)]
        private int $creationDate,
        #[Property(description: 'Modification date', type: 'integer', example: 327417600)]
        private int $modificationDate,
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

    public function getKey(): string
    {
        return $this->key;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function getConfig(): ?string
    {
        return $this->config;
    }

    public function getCtype(): string
    {
        return $this->ctype;
    }

    public function getInheritable(): bool
    {
        return $this->inheritable;
    }

    public function getCreationDate(): int
    {
        return $this->creationDate;
    }

    public function getModificationDate(): int
    {
        return $this->modificationDate;
    }
}
