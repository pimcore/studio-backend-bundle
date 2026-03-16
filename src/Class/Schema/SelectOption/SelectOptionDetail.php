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
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    schema: 'SelectOptionDetail',
    title: 'Select Option Detail',
    required: [
        'id',
        'group',
        'adminOnly',
        'useTraits',
        'implementsInterfaces',
        'selectOptions',
        'enumName',
        'isWriteable',
    ],
    type: 'object'
)]
final class SelectOptionDetail implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'ID of the select options configuration', type: 'string', example: 'EventStatus')]
        private readonly string $id,
        #[Property(description: 'Group name', type: 'string', example: 'system', nullable: true)]
        private readonly ?string $group,
        #[Property(description: 'Whether this configuration is restricted to admin', type: 'boolean', example: false)]
        private readonly bool $adminOnly,
        #[Property(description: 'PHP traits to use', type: 'string', example: '')]
        private readonly string $useTraits,
        #[Property(description: 'PHP interfaces to implement', type: 'string', example: '')]
        private readonly string $implementsInterfaces,
        #[Property(
            description: 'Select option entries',
            type: 'array',
            items: new Items(ref: SelectOptionData::class)
        )]
        /** @var SelectOptionData[] */
        private readonly array $selectOptions,
        #[Property(
            description: 'Fully qualified enum name',
            type: 'string',
            example: 'Pimcore\\Model\\DataObject\\SelectOptions\\EventStatus'
        )]
        private readonly string $enumName,
        #[Property(description: 'Whether the configuration is writeable', type: 'boolean', example: true)]
        private readonly bool $isWriteable,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
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

    /**
     * @return SelectOptionData[]
     */
    public function getSelectOptions(): array
    {
        return $this->selectOptions;
    }

    public function getEnumName(): string
    {
        return $this->enumName;
    }

    public function getIsWriteable(): bool
    {
        return $this->isWriteable;
    }
}
