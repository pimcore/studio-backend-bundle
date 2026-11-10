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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

#[Schema(
    schema: 'ClassificationStoreConfigurationKeyUnits',
    title: 'Classification Store Configuration Key Units',
    required: ['defaultUnit', 'validUnits'],
    type: 'object'
)]
final class KeyUnits implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    /**
     * @param string[]|null $validUnits
     */
    public function __construct(
        #[Property(
            description: 'ID of the unit the key preselects, as stored, or null where it names none',
            type: 'string',
            example: 'kg',
            nullable: true
        )]
        private readonly ?string $defaultUnit = null,
        #[Property(
            description: 'IDs of the units the key permits, as stored and in the order the key declares them,'
                . ' or null where the key restricts nothing and every unit is permitted',
            type: 'array',
            items: new Items(type: 'string'),
            example: ['kg', 'g'],
            nullable: true
        )]
        private readonly ?array $validUnits = null,
    ) {
    }

    public function getDefaultUnit(): ?string
    {
        return $this->defaultUnit;
    }

    /**
     * @return string[]|null
     */
    public function getValidUnits(): ?array
    {
        return $this->validUnits;
    }
}
