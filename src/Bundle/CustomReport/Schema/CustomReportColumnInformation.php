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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    schema: 'BundleCustomReportsColumnInformation',
    title: 'Bundle Custom Reports Column Information',
    required: ['name', 'disableOrderBy', 'disableFilterable', 'disableDropdownFilterable', 'disableLabel'],
    type: 'object',
)]
final class CustomReportColumnInformation implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Name', type: 'string', example: 'attributesAvailable')]
        private readonly string $name,
        #[Property(description: 'Disable order by', type: 'bool', example: false)]
        private readonly bool $disableOrderBy,
        #[Property(description: 'Disable filterable', type: 'bool', example: false)]
        private readonly bool $disableFilterable,
        #[Property(description: 'Disable dropdown filterable', type: 'bool', example: false)]
        private readonly bool $disableDropdownFilterable,
        #[Property(description: 'Disable label', type: 'bool', example: false)]
        private readonly bool $disableLabel,
    ) {

    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isDisableOrderBy(): bool
    {
        return $this->disableOrderBy;
    }

    public function isDisableFilterable(): bool
    {
        return $this->disableFilterable;
    }

    public function isDisableDropdownFilterable(): bool
    {
        return $this->disableDropdownFilterable;
    }

    public function isDisableLabel(): bool
    {
        return $this->disableLabel;
    }
}
