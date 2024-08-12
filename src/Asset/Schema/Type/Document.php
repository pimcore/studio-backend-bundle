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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Asset;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\AssetPermissions;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;

#[Schema(
    title: 'Document',
    required: [
        'pageCount',
        'imageThumbnailPath',
    ],
    type: 'object'
)]
class Document extends Asset
{
    public function __construct(
        #[Property(description: 'Page count', type: 'integer', example: 2)]
        private readonly ?int $pageCount,
        #[Property(
            description: 'Path to image thumbnail',
            type: 'string',
            example: '/path/to/document/imagethumbnail.jpg'
        )]
        private readonly ?string $imageThumbnailPath,
        bool $hasChildren,
        string $type,
        string $filename,
        string $mimeType,
        bool $hasMetadata,
        bool $workflowWithPermissions,
        string $fullPath,
        AssetPermissions $permissions,
        int $id,
        int $parentId,
        string $path,
        ElementIcon $icon,
        int $userOwner,
        int $userModification,
        ?string $locked,
        bool $isLocked,
        ?int $creationDate,
        ?int $modificationDate,
    ) {
        parent::__construct(
            $hasChildren,
            $type,
            $filename,
            $mimeType,
            $hasMetadata,
            $workflowWithPermissions,
            $fullPath,
            $permissions,
            $id,
            $parentId,
            $path,
            $icon,
            $userOwner,
            $userModification,
            $locked,
            $isLocked,
            $creationDate,
            $modificationDate
        );
    }

    public function getPageCount(): ?int
    {
        return $this->pageCount;
    }

    public function getImageThumbnailPath(): ?string
    {
        return $this->imageThumbnailPath;
    }
}
