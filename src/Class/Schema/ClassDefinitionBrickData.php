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
    schema: 'ClassDefinitionObjectBrickData',
    title: 'Class Definition Object Brick Data',
    required: ['key', 'fieldName'],
    type: 'object'
)]
class ClassDefinitionBrickData implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Key of the object brick', type: 'string', example: 'SaleInformation')]
        private readonly string $key,
        #[Property(description: 'Name of class definition field', type: 'string', example: 'saleInformation')]
        private readonly string $fieldName
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }
}
