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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Schema\AssetType;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\AssetVersion;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\Dimensions;

#[Schema(
    title: 'ImageVersion',
    type: 'object'
)]
readonly class ImageVersion extends AssetVersion
{
    public function __construct(
        string $fileName,
        ?string $temporaryFile,
        #[Property(description: 'creation date', type: 'integer', example: 1707312457)]
        private int $creationDate,
        #[Property(description: 'modification date', type: 'integer', example: 1707312457)]
        private ?int $modificationDate,
        #[Property(description: 'file size', type: 'integer', example: 41862)]
        private int $fileSize,
        #[Property(description: 'mime type', type: 'string', example: 'image/png')]
        private string $mimeType,
        #[Property(description: 'dimensions', type: Dimensions::class, example: '{"width":1920,"height":1080}')]
        private ?Dimensions $dimensions = null,
    ) {
        parent::__construct(
            $fileName,
            $temporaryFile
        );
    }

    public function getDimensions(): ?Dimensions
    {
        return $this->dimensions;
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
}
