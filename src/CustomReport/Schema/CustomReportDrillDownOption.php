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

namespace Pimcore\Bundle\StudioBackendBundle\CustomReport\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'BundleCustomReportsDrillDownOption',
    title: 'Bundle Custom Reports Drill Down Option',
    description: 'Drill down option data for custom reports',
    required: ['name', 'value'],
    type: 'object',
)]
final class CustomReportDrillDownOption implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(
            description: 'name',
            example: 'Full-Size',
            anyOf: [
                new Schema(type: 'string'),
                new Schema(type: 'integer'),
            ]
        )]
        private readonly int|string $name,
        #[Property(
            description: 'value',
            example: 'Full-Size',
            anyOf: [
                new Schema(type: 'string'),
                new Schema(type: 'integer'),
            ]
        )]
        private readonly null|string|int $value,
    ) {

    }

    public function getName(): string|int
    {
        return $this->name;
    }

    public function getValue(): null|string|int
    {
        return $this->value;
    }
}
