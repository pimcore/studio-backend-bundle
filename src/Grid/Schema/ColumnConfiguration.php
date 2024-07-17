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
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\AdditionalAttributesTrait;

/**
 * Contains all data to configure a grid column
 *
 * @internal
 */
#[Schema(
    title: 'GridColumnConfiguration',
    required: ['key', 'group', 'sortable', 'editable', 'localizable', 'type', 'config'],
    type: 'object'
)]
final class ColumnConfiguration implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Key', type: 'string', example: 'id')]
        private readonly string $key,
        #[Property(description: 'Group', type: 'string', example: 'system')]
        private readonly string $group,
        #[Property(description: 'Sortable', type: 'boolean', example: true)]
        private readonly bool $sortable,
        #[Property(description: 'Editable', type: 'boolean', example: false)]
        private readonly bool $editable,
        #[Property(description: 'Localizable', type: 'boolean', example: false)]
        private readonly bool $localizable,
        #[Property(description: 'Locale', type: 'string', example: 'en')]
        private readonly ?string $locale,
        #[Property(description: 'Type', type: 'string', example: 'integer')]
        private readonly string $type,
        #[Property(description: 'Frontend Type', type: 'string', example: 'integer')]
        private readonly string $frontendType,
        #[Property(description: 'Config', type: 'array', items: new Items(type: 'string'), example: ['key' => 'value'])]
        private readonly array $config,
    ) {
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function isEditable(): bool
    {
        return $this->editable;
    }

    public function isLocalizable(): bool
    {
        return $this->localizable;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getFrontendType(): string
    {
        return $this->frontendType;
    }
}
