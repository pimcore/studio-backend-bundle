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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Hydrator;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Document\SearchResult\DocumentSearchResultItem;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Document;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;

/**
 * @internal
 */
final class DocumentHydrator implements DocumentHydratorInterface
{
    public function hydrate(DocumentSearchResultItem $item): Document
    {
        return new Document(
            fullPath: $item->getFullPath(),
            id: $item->getId(),
            parentId: $item->getParentId(),
            path: $item->getPath(),
            icon: new ElementIcon('path', 'icon'), // TODO: Implement icon
            userOwner: $item->getUserOwner(),
            userModification: $item->getModificationDate(),
            locked: $item->getLocked(),
            isLocked: $item->isLocked(),
            creationDate: $item->getCreationDate(),
            modificationDate: $item->getUserModification(),
        );
    }
}
