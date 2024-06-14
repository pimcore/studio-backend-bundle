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
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    title: 'PredefinedProperty',
    required: ['id', 'name', 'key', 'type', 'ctype', 'inheritable', 'creationDate', 'modificationDate'],
    type: 'object'
)]
final class PredefinedProperty implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'id', type: 'string', example: 'alpha-numerical-value')]
        private readonly string $id,
        #[Property(description: 'name', type: 'string', example: 'Mister Proper')]
        private readonly string $name,
        #[Property(description: 'description', type: 'string', example: 'Detailed description of the property')]
        private readonly ?string $description,
        #[Property(description: 'key', type: 'string', example: 'Key for referencing')]
        private readonly string $key,
        #[Property(description: 'type', type: 'string', example: 'text')]
        private readonly string $type,
        #[Property(description: 'data', type: 'string', example: 'test')]
        private readonly ?string $data,
        #[Property(description: 'config', type: 'string', example: 'comma,separated,values')]
        private readonly ?string $config,
        #[Property(description: 'ctype', type: 'string', example: 'document')]
        private readonly string $ctype,
        #[Property(description: 'inheritable', type: 'boolean', example: false)]
        private readonly bool $inheritable,
        #[Property(description: 'Creation date', type: 'integer', example: 221846400)]
        private readonly int $creationDate,
        #[Property(description: 'Modification date', type: 'integer', example: 327417600)]
        private readonly int $modificationDate,
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

    public function isInheritable(): bool
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
