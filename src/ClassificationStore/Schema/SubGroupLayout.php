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

/**
 * @internal
 */
#[Schema(
    title: 'Classification Store Sub Group Layout',
    required: [
        'identifier',
        'name',
        'description',
    ],
    type: 'object'
)]
final readonly class SubGroupLayout
{
    public function __construct(
        #[Property(description: 'Identifier the key assignments name this sub group by', type: 'string')]
        private string $identifier,
        #[Property(description: 'Name', type: 'string', example: 'value')]
        private string $name,
        #[Property(description: 'Description', type: 'string', example: 'value', nullable: true)]
        private ?string $description = null,
    ) {
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
