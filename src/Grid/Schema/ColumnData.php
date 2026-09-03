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
            ref: InheritanceData::class,
            description: 'Inheritance data of the column field',
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
