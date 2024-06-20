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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    title: 'ImageVersion',
    required: ['fileName', 'creationDate', 'fileSize', 'mimeType', 'metadata'],
    type: 'object'
)]
final class ImageVersion implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'file name', type: 'string', example: 'myImageFile.png')]
        private readonly string $fileName,
        #[Property(description: 'creation date', type: 'integer', example: 1707312457)]
        private readonly int $creationDate,
        #[Property(description: 'modification date', type: 'integer', example: 1707312457)]
        private readonly ?int $modificationDate,
        #[Property(description: 'file size', type: 'integer', example: 41862)]
        private readonly int $fileSize,
        #[Property(description: 'mime type', type: 'string', example: 'image/png')]
        private readonly string $mimeType,
        #[Property(description: 'Metadata', type: 'array', items: new Items(ref: CustomMetadataVersion::class))]
        private readonly array $metadata,
        #[Property(description: 'dimensions', type: Dimensions::class, example: '{"width":1920,"height":1080}')]
        private readonly ?Dimensions $dimensions = null,

    ) {
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }



    public function getCreationDate(): int
    {
        return $this->creationDate;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getDimensions(): ?Dimensions
    {
        return $this->dimensions;
    }
}
