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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    title: 'PublishedState',
    required: ['elementId', 'elementType', 'isPublished'],
    type: 'object'
)]
final class PublishedState implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Element Id', type: 'integer', example: 14)]
        private readonly int $elementId,
        #[Property(description: 'Element Type', type: 'string', example: 'document')]
        private readonly string $elementType,
        #[Property(
            description: 'Is the element published, null for elements without a published state like assets',
            type: 'boolean',
            example: false,
            nullable: true
        )]
        private readonly ?bool $isPublished,
    ) {

    }

    public function getElementId(): int
    {
        return $this->elementId;
    }

    public function getElementType(): string
    {
        return $this->elementType;
    }

    public function getIsPublished(): ?bool
    {
        return $this->isPublished;
    }
}
