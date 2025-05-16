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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Service;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Document\SearchResult\DocumentSearchResultItem;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Document\SearchResult\SearchResultItem\Email;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Document\SearchResult\SearchResultItem\Page;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Document\SearchResult\SearchResultItem\Snippet;
use Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\ElementLockServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Hydrator\Document\PermissionsHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;

/**
 * @internal
 */
final readonly class HydratorService implements HydratorServiceInterface
{
    public function __construct(
        private ElementLockServiceInterface $elementLockService,
        private IconServiceInterface $iconService,
        private PermissionsHydratorInterface $permissionsHydrator,
    ) {
    }

    public function getBaseDocumentData(DocumentSearchResultItem $item): array
    {
        return [
            $item->getFullPath(),
            $item->isPublished(),
            $item->getType(),
            $item->getKey(),
            $item->isHasChildren(),
            $item->isHasWorkflowWithPermissions(),
            $this->permissionsHydrator->hydrate($item->getPermissions()),
            $item->getId(),
            $item->getParentId(),
            $item->getPath(),
            $this->iconService->getIconForDocument($item->getType()),
            $item->getUserOwner(),
            $item->getModificationDate(),
            $item->getLocked(),
            $this->elementLockService->isElementLocked(
                $item->getFullPath(),
                $item->getElementType()->value,
                $item->getLocked()
            ),
            $item->getCreationDate(),
            $item->getUserModification(),
        ];
    }

    public function getBasePageSnippetData(Email|Page|Snippet $item): array
    {
        return [
            $item->getController(),
            $item->getTemplate(),
            $item->getContentMainDocumentId(),
            $item->isSupportsContentMain(),
            $item->isMissingRequiredEditable(),
            $item->isStaticGeneratorEnabled(),
            $item->getStaticGeneratorLifetime(),
        ];
    }
}
