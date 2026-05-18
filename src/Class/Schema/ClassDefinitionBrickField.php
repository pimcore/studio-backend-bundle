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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'ClassDefinitionBrickField',
    title: 'Class Definition Object Brick Field',
    required: ['fieldName'],
    type: 'object'
)]
final class ClassDefinitionBrickField implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(
            description: 'Name of the class definition field of type object brick',
            type: 'string',
            example: 'saleInformation'
        )]
        private readonly string $fieldName,
    ) {
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }
}
