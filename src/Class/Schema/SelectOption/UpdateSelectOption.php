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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Schema\SelectOption;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'UpdateSelectOption',
    title: 'Schema used to update select option configurations',
    required: [
        'group',
        'adminOnly',
        'useTraits',
        'implementsInterfaces',
        'selectOptions',
    ],
    type: 'object'
)]
final readonly class UpdateSelectOption
{
    public function __construct(
        #[Property(description: 'Group name', type: 'string', example: 'system', nullable: true)]
        private ?string $group = null,
        #[Property(description: 'Whether this configuration is restricted to admin', type: 'boolean', example: false)]
        private bool $adminOnly = false,
        #[Property(description: 'PHP traits to use', type: 'string', example: '')]
        private string $useTraits = '',
        #[Property(description: 'PHP interfaces to implement', type: 'string', example: '')]
        private string $implementsInterfaces = '',
        #[Property(
            description: 'Select option entries',
            type: 'array',
            items: new Items(ref: SelectOptionData::class),
            nullable: true
        )]
        private ?array $selectOptions = null,
    ) {
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function isAdminOnly(): bool
    {
        return $this->adminOnly;
    }

    public function getUseTraits(): string
    {
        return $this->useTraits;
    }

    public function getImplementsInterfaces(): string
    {
        return $this->implementsInterfaces;
    }

    public function getSelectOptions(): ?array
    {
        return $this->selectOptions;
    }
}
