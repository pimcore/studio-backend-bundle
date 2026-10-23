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

namespace Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema(
    schema: 'VideoThumbnailSettings',
    title: 'Video Thumbnail Settings',
    required: [
        'name', 'description', 'group', 'videoBitrate', 'audioBitrate',
        'modificationDate', 'creationDate', 'filenameSuffix',
    ],
    type: 'object'
)]
final readonly class VideoThumbnailSettings
{
    public function __construct(
        #[Property(description: 'Thumbnail name', type: 'string', example: 'content')]
        private string $name,
        #[Property(description: 'Thumbnail description', type: 'string', example: 'thumbnail for content videos')]
        private ?string $description,
        #[Property(description: 'Thumbnail group', type: 'string', example: '')]
        private ?string $group,
        #[Property(description: 'Video bitrate in kbps', type: 'integer', example: 450)]
        private ?int $videoBitrate,
        #[Property(description: 'Audio bitrate in kbps', type: 'integer', example: 128)]
        private ?int $audioBitrate,
        #[Property(description: 'Modification date timestamp', type: 'integer', example: 1769430880, nullable: true)]
        private ?int $modificationDate,
        #[Property(description: 'Creation date timestamp', type: 'integer', example: 1565353556, nullable: true)]
        private ?int $creationDate,
        #[Property(description: 'Filename suffix', type: 'string', example: null, nullable: true)]
        private ?string $filenameSuffix,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function getVideoBitrate(): ?int
    {
        return $this->videoBitrate;
    }

    public function getAudioBitrate(): ?int
    {
        return $this->audioBitrate;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    public function getFilenameSuffix(): ?string
    {
        return $this->filenameSuffix;
    }
}
