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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Hydrator\Asset;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\SearchResultItem\Text as TextItem;
use Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\ElementLockServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Text;
use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;

/**
 * @internal
 */
final readonly class TextHydrator implements TextHydratorInterface
{
    public function __construct(
        private ElementLockServiceInterface $elementLockService,
        private IconServiceInterface $iconService,
        private PermissionsHydratorInterface $permissionsHydrator
    ) {
    }

    public function hydrate(TextItem $item): Text
    {
        return new Text(
            $item->isHasChildren(),
            $item->getType(),
            $item->getKey(),
            $item->getMimeType(),
            !empty($item->getMetaData()),
            $item->isHasWorkflowWithPermissions(),
            $item->getFullPath(),
            $this->permissionsHydrator->hydrate($item->getPermissions()),
            $item->getId(),
            $item->getParentId(),
            $item->getPath(),
            $this->iconService->getIconForAsset($item->getType(), $item->getMimeType()),
            $item->getUserOwner(),
            $item->getUserModification(),
            $item->getLocked(),
            $this->elementLockService->isElementLocked(
                $item->getFullPath(),
                $item->getElementType()->value,
                $item->getLocked()
            ),
            $item->getCreationDate(),
            $item->getModificationDate(),
            $item->getMetaData(),
            $item->getFileSize(),
        );
    }
}
