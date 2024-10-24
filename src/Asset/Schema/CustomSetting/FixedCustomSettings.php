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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Schema\CustomSetting;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'FixedCustomSettings',
    required: ['embeddedMetadata', 'embeddedMetadataExtracted'],
    type: 'object'
)]
final readonly class FixedCustomSettings
{
    public function __construct(
        #[Property(
            description: 'embedded meta data of the asset - array of any key-value pairs',
            type: 'array',
            items: new Items(),
            example: '{ FileSize: "265 KiB", MIMEType: "image/jpeg" }'
        )]
        private array $embeddedMetadata = [],
        #[Property(
            description: 'flag to indicate if the embedded meta data has been extracted from the asset',
            type: 'bool',
            example: true
        )]
        private bool $embeddedMetadataExtracted = false,
    ) {
    }

    public function getEmbeddedMetadata(): array
    {
        return $this->embeddedMetadata;
    }

    public function isEmbeddedMetadataExtracted(): bool
    {
        return $this->embeddedMetadataExtracted;
    }
}
