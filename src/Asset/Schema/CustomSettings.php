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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\CustomSettings\FixedCustomSettings;

/**
 * @internal
 */
#[Schema(
    title: 'CustomSettings',
    type: 'object'
)]
final readonly class CustomSettings
{
    public function __construct(
        #[Property(
            description: 'fixed custom settings',
            type: FixedCustomSettings::class,
            example: '{ embeddedMetadata: {foo: bar}, checksum: b3685e8348e7ac4d30d0268f7e58902a }')
        ]
        private ?FixedCustomSettings $fixedCustomSettings = null,
        #[Property(
            description: 'dynamic custom settings - can be any key-value pair',
            type: 'array',
            items: new Items(),
            example: '{ imageWidth: 1280, imageHeight: 720 }')
        ]
        private array $dynamicCustomSettings = [],
    ){

    }

    public function getFixedCustomSettings(): ?FixedCustomSettings
    {
        return $this->fixedCustomSettings;
    }

    public function getDynamicCustomSettings(): array
    {
        return $this->dynamicCustomSettings;
    }
}