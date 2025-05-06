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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Hydrator;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Document\SearchResult\DocumentSearchResultItem;
use Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\ElementLockServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Document;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;

/**
 * @internal
 */
final class DocumentHydrator implements DocumentHydratorInterface
{
    public function __construct(
        private ElementLockServiceInterface $elementLockService
    ) {
    }

    public function hydrate(DocumentSearchResultItem $item): Document
    {
        return new Document(
            fullPath: $item->getFullPath(),
            published: $item->isPublished(),
            type: $item->getType(),
            id: $item->getId(),
            parentId: $item->getParentId(),
            path: $item->getPath(),
            icon: new ElementIcon('path', 'icon'), // TODO: Implement icon
            userOwner: $item->getUserOwner(),
            userModification: $item->getModificationDate(),
            locked: $item->getLocked(),
            isLocked: $this->elementLockService->isElementLocked(
                $item->getFullPath(),
                $item->getElementType()->value,
                $item->getLocked()
            ),
            creationDate: $item->getCreationDate(),
            modificationDate: $item->getUserModification(),
        );
    }
}
