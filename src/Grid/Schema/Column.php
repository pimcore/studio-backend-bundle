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
 * Column contains all data + values that is needed for the grid
 *
 * @internal
 */
#[Schema(
    title: 'Grid Column Request',
    required: ['key', 'locale', 'type', 'config'],
    type: 'object'
)]
final class Column
{

    public function __construct(
        #[Property(description: 'Key', type: 'string', example: 'id')]
        private readonly string $key,
        #[Property(description: 'Locale', type: 'string', example: 'en')]
        private readonly ?string $locale,
        #[Property(description: 'Type', type: 'string', example: 'integer')]
        private readonly string $type,
        #[Property(description: 'Config', type: 'array', items: new Items(type: 'string'), example: ['key' => 'value'])]
        private readonly array $config,
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
}
