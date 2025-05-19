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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\EncryptedField;

/**
 * @internal
 */
#[Schema(
    title: 'Classification Store Collection',
    required: [
        'id',
        'name',
        'description',
        'definition',
    ],
    type: 'object'
)]
final readonly class KeyLayout
{
    public function __construct(
        #[Property(description: 'ID', type: 'integer', example: 42)]
        private int $id,
        #[Property(description: 'Name', type: 'string', example: 'value')]
        private string $name,
        #[Property(description: 'Description', type: 'string', example: 'value')]
        private string $description,
        #[Property(description: 'Layout Definition', type: 'object')]
        private EncryptedField|Data $definition,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDefinition(): EncryptedField|Data
    {
        return $this->definition;
    }
}
