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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Hydrator\Asset;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\SearchResultItem\Document as DocumentItem;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Hydrator\PermissionsHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Response\Asset\Document;
use Pimcore\Bundle\StudioBackendBundle\Service\IconServiceInterface;

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
        return new Document(
            $item->getPageCount(),
            $item->getImageThumbnail(),
            $this->iconService->getIconForAsset($item->getType(), $item->getMimeType()),
            $item->isHasChildren(),
            $item->getType(),
            $item->getKey(),
            $item->getMimeType(),
            $this->metaDataHydrator->hydrate($item->getMetaData()),
            $item->isHasWorkflowWithPermissions(),
            $item->getFullPath(),
            $item->getId(),
            $item->getParentId(),
            $item->getPath(),
            $item->getUserOwner(),
            $item->getUserModification(),
            $item->getLocked(),
            $item->isLocked(),
            $item->getCreationDate(),
            $item->getModificationDate(),
            $this->permissionsHydrator->hydrate($item->getPermissions())
        );
    }
}