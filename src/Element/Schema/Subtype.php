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
    title: 'Subtype',
    required: ['elementId', 'elementType', 'elementSubtype'],
    type: 'object'
)]
final class Subtype implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Element Id', type: 'integer', example: 14)]
        private readonly int $elementId,
        #[Property(description: 'Element Type', type: 'string', example: 'asset')]
        private readonly string $elementType,
        #[Property(description: 'Element Subtype', type: 'string', example: 'image')]
        private readonly string $elementSubtype,
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

    public function getElementSubtype(): string
    {
        return $this->elementSubtype;
    }
}
