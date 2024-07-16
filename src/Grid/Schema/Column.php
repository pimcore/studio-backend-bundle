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

/**
 * Column contains all data + values that is needed for the grid
 *
 * @internal
 */
#[Schema(
    title: 'Grid Column Request',
    required: ['key', 'type', 'config'],
    type: 'object'
)]
final readonly class Column
{
    public function __construct(
        #[Property(description: 'Key', type: 'string', example: 'id')]
        private string $key,
        #[Property(description: 'Locale', type: 'string', example: 'en')]
        private ?string $locale,
        #[Property(description: 'Type', type: 'string', example: 'system.integer')]
        private string $type,
        #[Property(description: 'Group', type: 'string', example: 'system')]
        private ?string $group,
        #[Property(description: 'Config', type: 'array', items: new Items(type: 'string'), example: ['key' => 'value'])]
        private array $config,
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

    public function getType(): string
    {
        return $this->type;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
