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

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\SearchResultItem\Text as TextItem;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Text;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator\PermissionsHydratorInterface;
use Pimcore\Bundle\StudioApiBundle\Service\IconServiceInterface;

final readonly class TextHydrator implements TextHydratorInterface
{
    public function __construct(
        private IconServiceInterface $iconService,
        private MetaDataHydratorInterface $metaDataHydrator,
        private PermissionsHydratorInterface $permissionsHydrator
    ) {
    }

    public function hydrate(TextItem $item): Text
    {
        $text = new Text($item->getId());

        $text->setParentId($item->getParentId());
        $text->setPath($item->getPath());
        $text->setUserOwner($item->getUserOwner());
        $text->setUserModification($item->getUserModification());
        $text->setLocked($item->getLocked());
        $text->setIsLocked($item->isLocked());
        $text->setCreationDate($item->getCreationDate());
        $text->setModificationDate($item->getModificationDate());
        $text->setPermissions($this->permissionsHydrator->hydrate($item->getPermissions()));
        $text->setUserModification($item->getUserModification());

        // asset specific stuff
        $text->setIconName($this->iconService->getIconForAsset($item->getType(), $item->getMimeType()));
        $text->setHasChildren($item->isHasChildren());
        $text->setType($item->getType());
        $text->setFilename($item->getKey());
        $text->setMimeType($item->getMimeType());
        $text->setMetaData($this->metaDataHydrator->hydrate($item->getMetaData()));
        $text->setWorkflowWithPermissions($item->isHasWorkflowWithPermissions());
        $text->setFullPath($item->getFullPath());

        return $text;
    }
}
