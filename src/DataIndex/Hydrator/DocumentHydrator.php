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
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Document\SearchResult\SearchResultItem\Page;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Document;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\PageSnippet\PageSnippet;
use  Pimcore\Bundle\StudioBackendBundle\Document\Service\HydratorServiceInterface;

/**
 * @internal
 */
final readonly class DocumentHydrator implements DocumentHydratorInterface
{
    public function __construct(
        private HydratorServiceInterface $hydratorService,
    ) {
    }

    public function hydrate(DocumentSearchResultItem $item): Document
    {
        $documentBaseData = $this->hydratorService->getBaseDocumentData($item);

        if ($item instanceof Page) {
            $documentBaseData[] = $item->getTitle();
            $documentBaseData[] = $item->getDescription();

            $searchData = $item->getSearchIndexData();
            $documentBaseData[] = (bool) ($searchData['system_fields']['staticGeneratorEnabled'] ?? false);

            return new PageSnippet(...$documentBaseData);
        }

        return new Document(...$documentBaseData);
    }
}
