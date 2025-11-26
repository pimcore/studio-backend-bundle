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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    schema: 'AssetUploadInfo',
    title: 'Asset Upload Info',
    required: ['exists', 'assetId'],
    type: 'object'
)]
final readonly class AssetInfo
{
    public function __construct(
        #[Property(description: 'True if asset exists', type: 'boolean', example: true)]
        private bool $exists,
        #[Property(description: 'Id of existing asset', type: 'integer', example: 83)]
        private ?int $assetId = null,
    ) {
    }

    public function isExists(): bool
    {
        return $this->exists;
    }

    public function getAssetId(): ?int
    {
        return $this->assetId;
    }
}
