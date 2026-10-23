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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    title: 'SimpleSearchAssetDetail',
    required: ['mimeType', 'thumbnail'],
    type: 'object'
)]
final class AssetSearchPreview extends SimpleSearchPreview
{
    public function __construct(
        #[Property(description: 'Mimetype', type: 'string', example: 'image/jpeg')]
        private readonly ?string $mimeType,
        #[Property(description: 'Thumbnail path', type: 'string', example: 'path/to/thumbnail')]
        private readonly ?string $thumbnail,
        int $id,
        string $elementType,
        string $type,
        ?int $userOwner,
        ?string $userOwnerName,
        ?int $userModification,
        ?string $userModificationName,
        ?int $creationDate,
        ?int $modificationDate,
    ) {
        parent::__construct(
            $id,
            $elementType,
            $type,
            $userOwner,
            $userOwnerName,
            $userModification,
            $userModificationName,
            $creationDate,
            $modificationDate
        );
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }
}
