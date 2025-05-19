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
    title: 'Hardlink',
    required: ['sourceId', 'propertiesFromSource', 'childrenFromSource'],
    type: 'object'
)]
final class Hardlink extends Document
{
    public function __construct(
        #[Property(description: 'Source ID', type: 'integer', example: 83)]
        private readonly ?int $sourceId,
        #[Property(description: 'Properties from source', type: 'bool', example: true)]
        private readonly bool $propertiesFromSource,
        #[Property(description: 'Children from source', type: 'bool', example: false)]
        private readonly bool $childrenFromSource,
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

    public function getSourceId(): ?int
    {
        return $this->sourceId;
    }

    public function isPropertiesFromSource(): bool
    {
        return $this->propertiesFromSource;
    }

    public function isChildrenFromSource(): bool
    {
        return $this->childrenFromSource;
    }
}
