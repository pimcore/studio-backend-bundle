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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\InheritanceData;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    title: 'GridColumnData',
    type: 'object'
)]
final class ColumnData implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Key', type: 'string', example: 'id')]
        private readonly string $key,
        #[Property(description: 'Locale', type: 'string', example: 'en')]
        private readonly ?string $locale,
        #[Property(description: 'Value', type: 'mixed', example: 73)]
        private readonly mixed $value,
        #[Property(description: 'Field Type of the column', type: 'string', example: 'input')]
        private readonly mixed $fieldType,
        #[Property(
            description: 'Inheritance data of the column field. Either a single InheritanceData object for a '
                . 'leaf field, or a nested map for localized fields, object bricks and classification stores.',
            anyOf: [
                new Schema(ref: InheritanceData::class),
                new Schema(type: 'object', additionalProperties: true),
            ],
            example: ['objectId' => 42, 'inherited' => true, 'inheritable' => true],
            nullable: true
        )]
        private readonly null|InheritanceData|array $inheritance = null
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getFieldType(): mixed
    {
        return $this->fieldType;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getInheritance(): null|InheritanceData|array
    {
        return $this->inheritance;
    }
}
