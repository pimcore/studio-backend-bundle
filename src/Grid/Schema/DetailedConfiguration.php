<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Grid\ColumnSchema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * Contains all data to configure a grid column
 *
 * @internal
 */
#[Schema(
    title: 'GridConfiguration',
    required: ['id', 'name', 'description'],
    type: 'object'
)]
final class DetailedConfiguration implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Name', type: 'string', example: 'My Configuration')]
        private readonly string $name,
        #[Property(description: 'Description', type: 'string', example: 'My Configuration Description')]
        private readonly string $description,
        #[Property(description: 'shareGlobal', type: 'boolean', example: false)]
        private readonly bool $shareGlobal,
        #[Property(description: 'saveFilter', type: 'boolean', example: false)]
        private readonly bool $saveFilter,
        #[Property(description: 'setAsFavorite', type: 'boolean', example: false)]
        private readonly bool $setAsFavorite,
        #[Property(description: 'sharedUsers', type: 'object', example: [42,1337])]
        private readonly array $sharedUsers,
        #[Property(description: 'sharedRoles', type: 'object', example: [42,1337])]
        private readonly array $sharedRoles,
        #[Property(description: 'columns', type: 'array', items: new Items(ref: ColumnSchema::class))]
        private readonly array $columns,
        #[Property(description: 'filter', type: 'array', items: new Items(ref: Filter::class))]
        private readonly array $filter,
        #[Property(description: 'Page Size', type: 'integer', example: 42)]
        private readonly int $pageSize = 25
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isShareGlobal(): bool
    {
        return $this->shareGlobal;
    }

    public function isSaveFilter(): bool
    {
        return $this->saveFilter;
    }

    public function isSetAsFavorite(): bool
    {
        return $this->setAsFavorite;
    }

    /**
     * @return int[]
     */
    public function getSharedUsers(): array
    {
        return $this->sharedUsers;
    }

    /**
     * @return int[]
     */
    public function getSharedRoles(): array
    {
        return $this->sharedRoles;
    }

    /**
     * @return ColumnSchema[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return Filter[]
     */
    public function getFilter(): array
    {
        return $this->filter;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

}
