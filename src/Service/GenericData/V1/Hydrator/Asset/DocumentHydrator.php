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

namespace Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator\Asset;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\SearchResultItem\Document as DocumentItem;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Document;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator\PermissionsHydratorInterface;
use Pimcore\Bundle\StudioApiBundle\Service\IconServiceInterface;

final readonly class DocumentHydrator implements DocumentHydratorInterface
{
    public function __construct(
        private IconServiceInterface $iconService,
        private MetaDataHydratorInterface $metaDataHydrator,
        private PermissionsHydratorInterface $permissionsHydrator
    ) {
    }

    public function hydrate(DocumentItem $item): Document
    {
        $document = new Document($item->getId());

        $document->setParentId($item->getParentId());
        $document->setPath($item->getPath());
        $document->setUserOwner($item->getUserOwner());
        $document->setUserModification($item->getUserModification());
        $document->setLocked($item->getLocked());
        $document->setIsLocked($item->isLocked());
        $document->setCreationDate($item->getCreationDate());
        $document->setModificationDate($item->getModificationDate());
        $document->setPermissions($this->permissionsHydrator->hydrate($item->getPermissions()));
        $document->setUserModification($item->getUserModification());

        // asset specific stuff
        $document->setIconName($this->iconService->getIconForAsset($item->getType(), $item->getMimeType()));
        $document->setHasChildren($item->isHasChildren());
        $document->setType($item->getType());
        $document->setFilename($item->getKey());
        $document->setMimeType($item->getMimeType());
        $document->setMetaData($this->metaDataHydrator->hydrate($item->getMetaData()));
        $document->setWorkflowWithPermissions($item->isHasWorkflowWithPermissions());
        $document->setFullPath($item->getFullPath());

        $document->setPageCount($item->getPageCount());
    }
}
