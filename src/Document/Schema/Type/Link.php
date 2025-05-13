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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Schema\Type;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Document;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocumentPermissions;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;

#[Schema(
    title: 'Link',
    required: ['internal', 'internalType', 'direct', 'linkType', 'href'],
    type: 'object'
)]
final class Link extends Document
{
    public function __construct(
        #[Property(description: 'Internal ID', type: 'integer', example: 83)]
        private readonly ?int $internal,
        #[Property(description: 'Internal type', type: 'string', example: 'asset')]
        private readonly ?string $internalType,
        #[Property(description: 'Direct', type: 'string', example: '/path/to/asset')]
        private readonly string $direct,
        #[Property(description: 'Link type', type: 'string', example: 'direct')]
        private readonly string $linkType,
        #[Property(description: 'Href', type: 'string', example: '/path/to/asset')]
        private readonly string $href,
        string $fullPath,
        bool $published,
        string $type,
        string $key,
        bool $hasChildren,
        bool $hasWorkflowWithPermissions,
        DocumentPermissions $permissions,
        int $id,
        int $parentId,
        string $path,
        ElementIcon $icon,
        int $userOwner,
        ?int $userModification,
        ?string $locked,
        bool $isLocked,
        ?int $creationDate,
        ?int $modificationDate,
    ) {
        parent::__construct(
            $fullPath,
            $published,
            $type,
            $key,
            $hasChildren,
            $hasWorkflowWithPermissions,
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

    public function getInternal(): ?int
    {
        return $this->internal;
    }

    public function getInternalType(): ?string
    {
        return $this->internalType;
    }

    public function getDirect(): string
    {
        return $this->direct;
    }

    public function getLinkType(): string
    {
        return $this->linkType;
    }

    public function getHref(): string
    {
        return $this->href;
    }
}
