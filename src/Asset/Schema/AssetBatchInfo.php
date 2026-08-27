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
    schema: 'AssetUploadBatchInfo',
    title: 'Asset Upload Batch Info',
    required: ['fileName', 'exists', 'assetId', 'accessDenied'],
    type: 'object'
)]
final readonly class AssetBatchInfo
{
    public function __construct(
        #[Property(description: 'Name of the checked file', type: 'string', example: 'file.jpg')]
        private string $fileName,
        #[Property(description: 'True if asset exists', type: 'boolean', example: true)]
        private bool $exists,
        #[Property(description: 'Id of existing asset', type: 'integer', example: 83)]
        private ?int $assetId = null,
        #[Property(
            description: 'True if an asset with that name exists but the current user may not view it. ' .
                'The name is therefore reported as not existing, since no ID can be handed out.',
            type: 'boolean',
            example: false
        )]
        private bool $accessDenied = false,
    ) {
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function isExists(): bool
    {
        return $this->exists;
    }

    public function getAssetId(): ?int
    {
        return $this->assetId;
    }

    public function isAccessDenied(): bool
    {
        return $this->accessDenied;
    }
}
