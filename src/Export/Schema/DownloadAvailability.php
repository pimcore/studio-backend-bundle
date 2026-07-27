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

namespace Pimcore\Bundle\StudioBackendBundle\Export\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'DownloadAvailability',
    title: 'Download Availability',
    required: ['available'],
    type: 'object'
)]
final class DownloadAvailability
{
    public function __construct(
        #[Property(
            description: 'Whether the exported file is still available for download',
            type: 'boolean',
            example: true
        )]
        private readonly bool $available,
    ) {
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }
}
