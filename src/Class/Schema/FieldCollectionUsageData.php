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

/**
 * @internal
 */
#[Schema(
    schema: 'FieldCollectionUsageData',
    title: 'Field Collection Usage Data',
    required: ['class', 'field'],
    type: 'object'
)]
final class FieldCollectionUsageData implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Name of the class using the field collection', type: 'string', example: 'Car')]
        private readonly string $class,
        #[Property(description: 'Name of the field in the class', type: 'string', example: 'myFieldCollection')]
        private readonly string $field,
    ) {
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getField(): string
    {
        return $this->field;
    }
}
